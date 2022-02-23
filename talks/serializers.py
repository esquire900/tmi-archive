from talks.models import Talk, Playlist
from rest_framework import serializers


class TalkSerializer(serializers.HyperlinkedModelSerializer):
    class Meta:
        model = Talk
        fields = ['id', 'title', 'mp3_url_clean', 'description',
                  'transcription']


class PlaylistSerializer(serializers.HyperlinkedModelSerializer):
    class Meta:
        model = Playlist
        fields = ['id', 'title', 'description', 'first_recording_date']
