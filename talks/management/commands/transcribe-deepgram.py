from django.core.management.base import BaseCommand
from talks.models import Talk
import os
import json
import datetime
import asyncio


def convert_deepgram_transcription(response):
    result = ['[00:00:00.0 Intro]']
    last_word = None
    first_word = True
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
    return "\r\n".join(result)  # same, needed for the audioplayer


class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):
        from deepgram import Deepgram  # manually install deepgram-sdk
        import asyncio, json
        import os

        TOKEN = os.getenv("DEEPGRAM_API_TOKEN")
        dg_client = Deepgram(TOKEN)
        base_path = '/var/www/vhosts/tmi-archive.com/mp3'

        talk_id = 16
        talk = Talk.objects.get(id=talk_id)

        fname = str(talk.audio_cleaned).split("/")[-1]
        mp3_path = f'{base_path}/mp3-cleaned/{fname}'
        transcription_path = f'{base_path}/transcriptions/{fname}.json'
        print(mp3_path, transcription_path)
        if not os.path.exists(transcription_path):
            async def do(mp3_path_arg, transcription_path_arg):
                with open(mp3_path_arg, 'rb') as audio:
                    source = {'buffer': audio, 'mimetype': 'audio/mp3'}
                    response = await dg_client.transcription.prerecorded(source, {'punctuate': True, 'diarize': True, })
                    with open(transcription_path_arg, 'w') as f:
                        json.dump(response, f)

            asyncio.run(do(mp3_path, transcription_path))
        else:
            with open(transcription_path, 'r') as f:
                transcription = json.load(f)
                talk.transcription_deepgram = json.dumps(transcription)
                talk.transcription = convert_deepgram_transcription(transcription)
                talk.auto_add_user_data = False
                talk.save()
