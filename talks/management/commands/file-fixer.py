import os

from django.core.management.base import BaseCommand

from talks.models import Talk
import shutil


class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):

        talks = Talk.objects.order_by('id').all()[:]

        for talk in talks:
            try:
                file = talk.audio_original.path
            except ValueError:
                continue

            if "audio/original/" not in file:
                continue

            file = file.replace('audio/original/', '')

            if not (os.path.exists(file)):
                print(file)
                continue

            folder = f"/data/tmi-archive-mp3/{talk.id}"
            new_file = f"{folder}/original.mp3"

            if not os.path.exists(folder):
                os.makedirs(folder)
            shutil.copy(file, new_file)
            talk.auto_add_user_data = False
            talk.audio_original = f"{talk.id}/original.mp3"
            talk.updated_by_id = 1
            talk.save()
            print(talk.id)
