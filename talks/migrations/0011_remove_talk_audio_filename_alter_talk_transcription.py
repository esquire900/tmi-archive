# Generated by Django 4.0.8 on 2023-01-02 13:09

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('talks', '0010_remove_talk_transcription_deepgram_and_more'),
    ]

    operations = [
        migrations.RemoveField(
            model_name='talk',
            name='audio_filename',
        ),
        migrations.AlterField(
            model_name='talk',
            name='transcription',
            field=models.TextField(blank=True, help_text='The transcription of the audio is formatted in a specific way:"[timestamp @speaker_name] all text of paragraph (new line)". For example: <br>[0:00:00.0 @student] Some question.<br>[0:00:10.4 @culadasa] Answer to question.', null=True),
        ),
    ]
