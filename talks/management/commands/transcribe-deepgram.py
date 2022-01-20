from django.core.management.base import BaseCommand
from talks.models import Talk
import os
import json
import datetime
import asyncio


def convert_deepgram_transcription(response):
    result = ['[00:00:00.0 Intro]']
    last_word = None
    first_word =True
    current_sentence = []
    for item in response['results']['channels'][0]['alternatives'][0]['words']:
        #   item looks like
        #   {'word': 'we',
        # 'start': 0.896973,
        # 'end': 1.0963004,
        # 'confidence': 0.8210447,
        # 'speaker': 0,
        # 'punctuated_word': 'we'}
        new_sentence = item['punctuated_word'].lower() != item['punctuated_word']
        if last_word is not None:
            new_sentence &= last_word[-1] in ['.', '?', '!']
        if new_sentence or first_word:
            # this is a new sentence
            if not first_word:
                result.append(' '.join(current_sentence))
            first_word = False

            current_sentence = []
            # this format is specifically for the player, has to be like
            # https://main.podigee-cdn.net/ppp/samples/transcript.txt
            dt = str(datetime.timedelta(seconds=round(item['start'], 1)))[:9]
            current_sentence.append(f'[0{dt} @speaker_{item["speaker"]}]')
        current_sentence.append(item['punctuated_word'])
        last_word = item['punctuated_word']
    return "\r\n".join(result) #same, needed for the audioplayer


class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):
        talk_id = 9
        transcription_file = '/data/projects/tmi_archive/tmi-archive/transcriptions/tmi-archive-5LVKWJMONVAU.json'
        transcription = json.load(open(transcription_file))
        talk = Talk.objects.get(id=talk_id)
        talk.transcription_deepgram = json.dumps(transcription)
        talk.transcription = convert_deepgram_transcription(transcription)
        talk.auto_add_user_data = False
        talk.save()
