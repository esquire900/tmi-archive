import copy
import datetime

import reversion
from crum import get_current_user
from django.contrib.auth import get_user_model
from django.core.validators import FileExtensionValidator
from django.db import models, transaction
from django.urls import reverse
from dynamic_filenames import FilePattern
from tinymce.models import HTMLField
from django_enum import EnumField
from django.template.defaultfilters import slugify  # new


@reversion.register()
class Talk(models.Model):
    title = models.CharField(max_length=300, null=False)
    description = HTMLField(null=True, blank=True,
                            help_text="A short description about the talk. Not the transcription itself.")
    audio_original = models.FileField(null=True, blank=True,
                                      upload_to=FilePattern(
                                          filename_pattern='{instance.id}/original.mp3'),
                                      validators=[FileExtensionValidator(['mp3'])])
    audio_cleaned = models.FileField(null=True, blank=True,
                                     upload_to=FilePattern(
                                         filename_pattern='{instance.id}/cleaned.mp3'),
                                     validators=[FileExtensionValidator(['mp3'])])
    recorded_date = models.DateField(null=True, blank=True, help_text='Date when talk was recorded, if known.')

    original_file_name = models.CharField(max_length=300, blank=True, null=True)

    created_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='created_by')
    updated_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='updated_by')

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    auto_add_user_data = True

    whisper_transcription = models.JSONField(null=True, blank=True)

    transcription = models.TextField(null=True,
                                     blank=True,
                                     help_text='The transcription of the audio is formatted in a specific way:'
                                               '"[timestamp @speaker_name] all text of paragraph (new line)". For example: <br>'
                                               '[0:00:00.0 @student] Some question.<br>'
                                               '[0:00:10.4 @culadasa] Answer to question.'
                                     )

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
    def slug(self):
        return slugify(self.title)

    @property
    def audio_url_original(self):
        if not self.has_audio:
            return None
        return reverse('talk_download_original', kwargs={'pk': self.id})

    @property
    def has_audio(self) -> bool:
        if self.audio_original is None:
            return False
        try:
            str(self.audio_original.path)
        except ValueError:
            return False
        return True

    @property
    def has_cleaned_audio(self) -> bool:
        if not self.has_audio:
            return False
        if self.audio_cleaned is None:
            return False
        try:
            str(self.audio_cleaned.path)
        except ValueError:
            return False
        return True

    @property
    def transcription_text(self):
        if self.transcription is None:
            return None
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
    def transcription_player_formatted(self):
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


class TalkMetric(models.Model):
    class MetricType(models.IntegerChoices):
        VIEW = 1, 'view'
        DOWNLOAD = 2, 'download'

    talk = models.ForeignKey('Talk', on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)
    metric_type = EnumField(MetricType)
    user = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='created_by_talk_metric',
                             null=True)
    ip = models.GenericIPAddressField(null=True, blank=True)

    @staticmethod
    def track(talk: Talk, metric_type: MetricType, request=None):

        def get_client_ip(request):
            x_forwarded_for = request.META.get('HTTP_X_FORWARDED_FOR')
            if x_forwarded_for:
                ip = x_forwarded_for.split(',')[0]
            else:
                ip = request.META.get('REMOTE_ADDR')
            return ip

        user = None
        ip = None
        if request is not None:
            if not request.user.is_anonymous:
                user = request.user
            ip = get_client_ip(request)

        metric = TalkMetric(
            talk=talk,
            metric_type=metric_type,
            created_at=datetime.datetime.utcnow(),
            user=user,
            ip=ip
        )
        metric.save()
