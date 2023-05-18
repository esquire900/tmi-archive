from talks.models import Talk, Playlist
from rest_framework import serializers

from rest_framework.reverse import reverse


class TalkSerializer(serializers.HyperlinkedModelSerializer):

    def audio_url_compute(self, talk: Talk):
        if not talk.has_audio:
            return None
        return reverse('talk_download', kwargs={'pk': talk.id}, request=self.context['request'])

    def audio_url_compute_original(self, talk: Talk):
        if not talk.has_audio:
            return None
        return reverse('talk_download_original', kwargs={'pk': talk.id}, request=self.context['request'])

    audio_url = serializers.SerializerMethodField('audio_url_compute')
    audio_original_url = serializers.SerializerMethodField('audio_url_compute_original')

    class Meta:
        model = Talk
        fields = ['id', 'title', 'audio_url', 'audio_original_url', 'description', 'has_audio',
                  'original_file_name', 'recorded_date']


class PlaylistSerializer(serializers.HyperlinkedModelSerializer):
    class Meta:
        model = Playlist
        fields = ['id', 'title', 'description', 'first_recording_date']
