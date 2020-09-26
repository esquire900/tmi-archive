import os

import pandas as pd
from bs4 import BeautifulSoup
from django.core.management.base import BaseCommand

from talks.models import Talk


class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):
        df = pd.read_json('./_other/scraper/articles-selenium.json')
        audio_path = '/home/script/_tmp/dhamma'
        for i, row in df.iloc[3:].iterrows():
            title = row.title
            content = BeautifulSoup(row.article, "html.parser").find('div', {'class': 'entry-content'})
            content = str(content).replace('<div class="entry-content">', '')[:-6]
            content = BeautifulSoup(content, "html.parser")

            for div in content.find_all("div", {'class': 'powerpress_player'}):
                div.decompose()
            for div in content.find_all("p", {'class': 'powerpress_links'}):
                div.decompose()
            content = str(content)
            talk = Talk(
                title=title,
                description=content,
                updated_by_id=1,
                created_by_id=1
            )
            talk.auto_add_user_data = False
            talk.save()

            if row.audio_link is not None:

                from django.core.files import File
                file_name = row.audio_link.replace('?_=1', '').split('/')[-1]
                orig_audio = '{}/mp3/{}'.format(audio_path, file_name)
                if os.path.exists(orig_audio):
                    orig_audio = File(open(orig_audio, 'rb'))
                    talk.audio_original.save('new', orig_audio)

                clean_audio = '{}/mp3-cleaned/{}'.format(audio_path, file_name)
                if os.path.exists(clean_audio):
                    clean_audio = File(open(clean_audio, 'rb'))
                    talk.audio_cleaned.save('new', clean_audio)
