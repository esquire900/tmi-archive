# Generated by Django 4.0.8 on 2023-01-03 10:52

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('talks', '0014_rename_created_by_talkmetric_user_talkmetric_ip'),
    ]

    operations = [
        migrations.AddField(
            model_name='talk',
            name='whisper_transcription',
            field=models.JSONField(blank=True, null=True),
        ),
    ]
