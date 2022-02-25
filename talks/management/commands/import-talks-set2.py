import glob
import os
import shutil
import pandas as pd
from bs4 import BeautifulSoup
from django.core.management.base import BaseCommand

from talks.models import Talk

import uuid
import base64


class Command(BaseCommand):
    help = 'One-off script to import talks from set 2 (audio files only)'

    def handle(self, *args, **options):
        for file in sorted(glob.glob('/var/www/vhosts/tmi-archive.com/mp3/import2/clean/*.mp3')):
            title = file.split('/')[-1].replace('.mp3', '').replace('-', ' ').replace('_', ' ')
            title = f'Imported - {title}'
            existing = Talk.objects.filter(title=title).count()
            if existing > 0:
                continue
            id_ = uuid.uuid4()
            id_ = base64.b32encode(id_.bytes).decode("utf-8").rstrip("=\n")[:12]
            new_file_dir = '/var/www/vhosts/tmi-archive.com/mp3.tmi-archive.com'
            new_file_name = f"tmi-archive-{id_}.mp3"
            shutil.copy(file, new_file_dir + '/' + new_file_name)
            talk = Talk(
                title=title,
                updated_by_id=1,
                created_by_id=1,
                original_file_name=file.split('/')[-1],
                audio_filename=new_file_name,
                audio_cleaned=new_file_name,
                audio_original=new_file_name
            )
            talk.auto_add_user_data = False
            talk.save()
            print(title,talk.id)
