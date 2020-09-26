import reversion
from crum import get_current_user
from django.contrib.auth import get_user_model
from django.core.validators import FileExtensionValidator
from django.db import models
from django.urls import reverse
from dynamic_filenames import FilePattern
from taggit.managers import TaggableManager


@reversion.register()
class Talk(models.Model):
    title = models.CharField(max_length=300, null=False)
    description = models.TextField(null=True)
    audio_original = models.FileField(null=True, blank=True,
                                      upload_to=FilePattern(filename_pattern='/audio/original/{uuid:.30base32}.mp3'),
                                      validators=[FileExtensionValidator(['mp3'])])
    audio_cleaned = models.FileField(null=True, blank=True,
                                     upload_to=FilePattern(filename_pattern='/audio/original/{uuid:.30base32}.mp3'),
                                     validators=[FileExtensionValidator(['mp3'])])
    created_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='created_by')
    updated_by = models.ForeignKey(get_user_model(), on_delete=models.CASCADE, related_name='updated_by')

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    auto_add_user_data = True
    tags = TaggableManager()

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
