# Generated by Django 4.0.8 on 2023-01-03 11:31

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('talks', '0015_talk_whisper_transcription'),
    ]

    operations = [
        migrations.AddField(
                    model_name='talk',
                    name='audio_length',
                    field=models.DurationField(blank=True, null=True),
                ),
    ]