# Generated by Django 3.2.11 on 2022-02-25 10:25

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('talks', '0008_auto_20220223_1001'),
    ]

    operations = [
        migrations.AddField(
            model_name='talk',
            name='original_file_name',
            field=models.CharField(blank=True, max_length=300, null=True),
        ),
    ]
