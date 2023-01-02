import os

from django.core.management.base import BaseCommand

from talks.models import Talk


class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):

        talks = Talk.objects.all()

        for talk in talks:
            fname = talk.audio_cleaned
            if fname is None:
                continue
            fname = str(fname).replace('original/', '').replace('audio/', '').replace('cleaned/', '')
            file_path = f'/var/www/vhosts/tmi-archive.com/mp3.tmi-archive.com/{fname}'
            if not (os.path.exists(file_path)):
                print(file_path)
