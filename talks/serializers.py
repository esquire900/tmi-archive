from talks.models import Talk, Playlist
from rest_framework import serializers


class TalkSerializer(serializers.HyperlinkedModelSerializer):
    class Meta:
        model = Talk
        fields = ['id', 'title', 'audio_url_original', 'audio_url', 'description',
                  'transcription', 'original_file_name', 'recorded_date']


class PlaylistSerializer(serializers.HyperlinkedModelSerializer):
    class Meta:
        model = Playlist
        fields = ['id', 'title', 'description', 'first_recording_date']
