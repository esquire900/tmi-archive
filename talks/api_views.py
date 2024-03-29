from django.http import HttpResponse, HttpResponseNotFound, FileResponse
from django.shortcuts import get_object_or_404

from .models import Talk, TalkMetric
from .models import Playlist

from rest_framework import viewsets
from rest_framework import permissions
from talks.serializers import TalkSerializer, PlaylistSerializer
from rest_framework.permissions import BasePermission, IsAuthenticated, SAFE_METHODS
from rest_framework.decorators import action
from rest_framework.response import Response


class ReadOnly(BasePermission):
    def has_permission(self, request, view):
        return request.method in SAFE_METHODS


class TalkViewSet(viewsets.ModelViewSet):
    """
    API endpoint that allows users to be viewed or edited.
    """
    queryset = Talk.objects.all().order_by('-id')
    serializer_class = TalkSerializer
    permission_classes = [permissions.IsAuthenticated | ReadOnly]


class PlaylistViewSet(viewsets.ModelViewSet):
    """
    API endpoint that allows users to be viewed or edited.
    """
    queryset = Playlist.objects.all().order_by('-id')
    serializer_class = PlaylistSerializer
    permission_classes = [permissions.IsAuthenticated | ReadOnly]


def talk_transcription(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    response = HttpResponse(talk.transcription_player_formatted, content_type="text/plain", charset='utf-8')
    response["Access-Control-Allow-Origin"] = "*"
    return response


def talk_transcription_json(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    response = HttpResponse(talk.transcription, content_type="text/json", charset='utf-8')
    response["Access-Control-Allow-Origin"] = "*"
    return response


def download_transcription(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    return HttpResponse(talk.transcription_text, content_type='text/plain')


def download_audio(request, pk):
    return download_audio_response(request, pk, False)


def download_audio_original(request, pk):
    return download_audio_response(request, pk, True)


def download_audio_response(request, pk, original=False):
    talk = get_object_or_404(Talk, pk=pk)
    if not talk.has_audio:
        return HttpResponseNotFound(f'No audio file found for this talk (id: {pk})')
    if original is False:
        if talk.has_cleaned_audio:
            file = talk.audio_cleaned.path
        else:
            file = talk.audio_original.path

    else:
        file = talk.audio_original.path

    TalkMetric.track(talk, TalkMetric.MetricType.DOWNLOAD, request)

    return FileResponse(open(file, 'rb'), filename=f"tmi-archive-{talk.id}-{talk.slug}.mp3")
