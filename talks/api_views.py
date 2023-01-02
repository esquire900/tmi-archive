from django.http import HttpResponse,  HttpResponseNotFound, FileResponse
from django.shortcuts import get_object_or_404

from .models import Talk, TalkMetric
from .models import Playlist

from rest_framework import viewsets
from rest_framework import permissions
from talks.serializers import TalkSerializer, PlaylistSerializer


class TalkViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint that allows users to be viewed or edited.
    """
    queryset = Talk.objects.all().order_by('-id')
    serializer_class = TalkSerializer
    permission_classes = [permissions.AllowAny]


class PlaylistViewSet(viewsets.ReadOnlyModelViewSet):
    """
    API endpoint that allows users to be viewed or edited.
    """
    queryset = Playlist.objects.all().order_by('-id')
    serializer_class = PlaylistSerializer
    permission_classes = [permissions.AllowAny]


def talk_transcription(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    response = HttpResponse(talk.transcription_player_formatted, content_type="text/plain", charset='utf-8')
    response["Access-Control-Allow-Origin"] = "*"
    return response


def download_transcription(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    return HttpResponse(talk.transcription_text, content_type='text/plain')


def download_audio(request, pk, audio_type='cleaned'):
    talk = get_object_or_404(Talk, pk=pk)
    if not talk.has_audio:
        return HttpResponseNotFound(f'No audio file found for this talk (id: {pk}, audio_type:{audio_type})')
    if audio_type == 'cleaned':
        file = talk.audio_cleaned
    else:
        file = talk.audio_original

    user = None
    if not request.user.is_anonymous:
        user = request.user
    TalkMetric.track(talk, TalkMetric.MetricType.DOWNLOAD, user)

    return FileResponse(open(file.path, 'rb'), filename=f"tmi-archive-{talk.id}.mp3")
