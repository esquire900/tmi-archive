import copy

import reversion
from crum import get_current_user
from django.contrib.auth import get_user_model
from django.core.validators import FileExtensionValidator
from django.db import models, transaction
from django.urls import reverse
from dynamic_filenames import FilePattern
from tinymce.models import HTMLField


@reversion.register()
class Talk(models.Model):
    title = models.CharField(max_length=300, null=False)
    description = HTMLField(null=True)
    audio_original = models.FileField(null=True, blank=True,
                                      upload_to=FilePattern(
                                          filename_pattern='audio/original/tmi-archive-{uuid:.12base32}.mp3'),
                                      validators=[FileExtensionValidator(['mp3'])])
    audio_cleaned = models.FileField(null=True, blank=True,
                                     upload_to=FilePattern(
                                         filename_pattern='audio/cleaned/tmi-archive-{uuid:.12base32}.mp3'),
                                     validators=[FileExtensionValidator(['mp3'])])

    audio_filename = models.CharField(max_length=300, blank=True, null=True)

    created_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='created_by')
    updated_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='updated_by')

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    auto_add_user_data = True

    transcription = models.TextField(null=True,
                                     help_text='The transcription of the audio is formatted in a specific way:'
                                               '"[timestamp @speaker_name] all text of paragraph (new line)". For example: <br>'
                                               '[0:00:00.0 @student] Some question.<br>'
                                               '[0:00:10.4 @culadasa] Answer to question.'
                                     )
    transcription_deepgram = models.TextField(null=True)

    def __str__(self):
        return self.title

    def save(self, *args, **kwargs):
        if self.auto_add_user_data:
            if self.pk is None:
                self.created_by = get_current_user()
            self.updated_by = get_current_user()

        res = super(Talk, self).save(*args, **kwargs)
        return res

    def get_absolute_url(self):
        return reverse('talk_view', kwargs={'pk': self.id})

    @property
    def mp3_url_clean(self):
        if self.audio_cleaned is None:
            return None
        try:
            file_name = str(self.audio_cleaned)
        except ValueError:
            return None
        file_name = file_name.split('/')[-1]
        return f'https://mp3.tmi-archive.com/{file_name}'

    @property
    def transcription_text(self):
        if self.transcription is None:
            return '(no transcription available)'
        sentences = self.transcription.split('\r\n')
        sentences = [s.split(']')[1] if ']' in s else s for s in sentences]
        paragraphs = []
        paragraph = ''
        for sentence in sentences:
            if len(paragraph) > 500:
                paragraphs.append(copy.copy(paragraph))
                paragraph = ''
            paragraph += str(sentence)

        paragraphs.append(paragraph)
        return "<br><br>".join(paragraphs)

    @property
    def transcription_for_audio(self):
        transcription = self.transcription
        for i in range(10):
            transcription = transcription.replace(f'@speaker_{i}', '')
        transcription = transcription.replace(' ]', ' -]')
        # the player needs very specific lines:
        # [00:00:03.6-] So anybody has on?
        sentences = transcription.split('\r\n')
        paragraphs = []
        paragraph = ''
        for sentence in sentences:
            if len(paragraph) > 500:
                paragraphs.append(paragraph)
                paragraph = ''
            if len(paragraph) > 0:
                sentence = sentence.split(']')[1]
            paragraph += str(sentence)
        paragraphs.append(paragraph)
        return "\r\n".join(paragraphs)


class SortableManyToManyField(models.ManyToManyField):
    @transaction.atomic
    def save_form_data(self, instance, data):
        field = getattr(instance, self.attname)
        field.clear()
        for order, item in enumerate(data):
            field.add(item, through_defaults={'order': order})


class Playlist(models.Model):
    title = models.CharField(max_length=300)
    description = HTMLField(null=True)
    first_recording_date = models.DateField(null=True, blank=True)
    talks = SortableManyToManyField(Talk, through='PlaylistTalk')

    created_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='created_by_playlist_talk')
    updated_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='updated_by_playlist_talk')

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['-first_recording_date']

    def __str__(self):
        return self.title

    def get_absolute_url(self):
        return reverse('playlist_view', kwargs={'pk': self.id})

    def clean(self):
        try:
            self.created_by
        except Exception as e:
            self.created_by = get_current_user()
        self.updated_by = get_current_user()

    def sorted_talks(self):
        return self.talks.all().order_by('playlisttalk__order')

class PlaylistTalk(models.Model):
    playlist = models.ForeignKey('Playlist', on_delete=models.CASCADE)
    talk = models.ForeignKey('Talk', on_delete=models.CASCADE)
    order = models.PositiveSmallIntegerField(default=0)

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ['order']

    def __str__(self):
        return f'{self.playlist}: {self.talk}'
