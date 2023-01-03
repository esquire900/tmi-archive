
from django.core.management.base import BaseCommand

from talks.models import Talk

from dateutil.parser import parse

class Command(BaseCommand):
    help = 'One-off script to import all scraped talks and audio-files'

    def handle(self, *args, **options):

        talks = Talk.objects.order_by('id').all()[:]

        for talk in talks:
            if talk.recorded_date is None:
                try:
                    parsed = parse(talk.title, fuzzy_with_tokens=True)
                except Exception as e:
                    continue
                date = parsed[0].date()
                if date.year >= 2022:
                    continue # not possible
                talk.recorded_date = date
                talk.auto_add_user_data = False
                talk.save()
                print(parsed[0].date(), talk.title)
