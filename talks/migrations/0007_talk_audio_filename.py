# Generated by Django 3.2.11 on 2022-02-23 09:00

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('talks', '0006_talk_transcription_talk_transcription_deepgram_and_more'),
    ]

    operations = [
        migrations.AddField(
            model_name='talk',
            name='audio_filename',
            field=models.CharField(blank=True, max_length=300, null=True),
        ),
    ]
