import datetime
import os

from django.core.management.base import BaseCommand

from talks.models import Talk
import shutil

from mutagen.mp3 import MP3


class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):

        talks = Talk.objects.order_by('id').where(audio_length__is_null=True).all()[:]

        for talk in talks:
            try:
                audio = MP3(talk.audio_original.path)
                talk.auto_add_user_data = False
                talk.audio_length_seconds = datetime.timedelta(seconds=audio.info.length)
                talk.updated_by_id = 1
                talk.save()
                print(talk.id)
                break
            except Exception as e:
                continue
