import os

from django.core.management.base import BaseCommand

from talks.models import Talk
import shutil
import json
import hashlib


class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):

        talks = Talk.objects.order_by('id').all()[:]
        with open('/var/www/vhosts/tmi-archive.com/httpdocs/current/_other/scraper/hashes.json', 'r') as f:
            data = json.load(f)

        for talk in talks:
            if talk.original_file_name is not None:
                continue

            hasher = hashlib.md5()
            try:
                with open(talk.audio_original.path, "rb") as afile:
                    buf = afile.read()
                    hasher.update(buf)
                hash_ = hasher.hexdigest()
                if hash_ in data.keys():
                    talk.original_file_name = data[hash_]
                    talk.auto_add_user_data = False
                    talk.save()
            except ValueError as e:
                continue
