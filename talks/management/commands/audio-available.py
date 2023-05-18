import os

from django.core.management.base import BaseCommand

from talks.models import Talk
import shutil
import json
import hashlib
import mutagen
from mutagen.mp3 import MP3

class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):

        talks = Talk.objects.order_by('id').all()[:]
        for talk in talks:
            if not talk.audio_original:
                continue
            path = f'/data/tmi-archive-mp3/{talk.id}/cleaned_fullsubnet.mp3'
            if os.path.exists(path):
                talk.audio_cleaned = f'{talk.id}/cleaned_fullsubnet.mp3'
                talk.auto_add_user_data = False
                talk.save()
                print(talk.audio_cleaned)
