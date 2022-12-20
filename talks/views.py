import annotate as annotate
from django.contrib.auth.decorators import login_required
from django.db.models import Q
from django.http import HttpResponse, JsonResponse
from django.shortcuts import render
from django.shortcuts import get_object_or_404
from django.views import generic
from django.contrib.auth.decorators import login_required
from django.db.models import Q

from .forms import TalkForm, PlaylistForm
from .models import Talk
from .models import Playlist

from django.views.decorators.cache import cache_page

from django.contrib.postgres.search import SearchQuery, SearchRank, SearchVector, SearchHeadline


class IndexView(generic.ListView):
    template_name = 'talk/list.html'
    fields = ['title']
    paginate_by = 20

    # context_object_name = 'latest_question_list'

    def get_queryset(self):
        """Return the last five published questions."""
        self.query = self.request.GET.get('q')
        queryset = Talk.objects.order_by('id')

        if self.query:
            vector = SearchVector('title', config='english', weight='A') \
                     + SearchVector('description', config='english', weight='B') \
                     + SearchVector('transcription', config='english', weight='C')
            query = SearchQuery(self.query)

            queryset = Talk.objects.annotate(rank=SearchRank(vector, query, cover_density=True)).order_by('-rank')
        return queryset

    def get_context_data(self, **kwargs):
        # Call the base implementation first to get a context
        context = super(IndexView, self).get_context_data(**kwargs)
        # Add in the publisher
        context['search_query'] = self.request.GET.get('q') or ''
        context['pagination_extra'] = f'q={self.query}' if self.query else None
        return context


class DetailView(generic.DetailView):
    model = Talk
    template_name = 'talk/view.html'

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['audio_url'] = self.get_object().mp3_url_clean
        return context


class NewDetailOriginalView(generic.DetailView):
    model = Talk
    template_name = 'talk/view_new.html'

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['audio_url'] = self.get_object().mp3_url_clean_original
        return context


class NewDetailView(DetailView):
    model = Talk
    template_name = 'talk/view_new.html'

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context['audio_url'] = self.get_object().mp3_url_clean
        return context



def talk_transcription(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    response = HttpResponse(talk.transcription_for_audio, content_type="text/plain", charset='utf-8')
    response["Access-Control-Allow-Origin"] = "*"
    return response


class UpdateView(generic.UpdateView):
    model = Talk
    template_name = 'talk/edit.html'
    form_class = TalkForm


class PlaylistUpdateView(generic.UpdateView):
    model = Playlist
    template_name = 'playlist/edit.html'
    form_class = PlaylistForm


class PlaylistCreateView(generic.CreateView):
    model = Playlist
    template_name = 'playlist/create.html'
    form_class = PlaylistForm


def playlist_index(request):
    return render(
        request, 'playlist/index.html', {'playlists': Playlist.objects.all()}
    )


def playlist_view(request, pk):
    playlist = get_object_or_404(Playlist, pk=pk)
    return render(request, 'playlist/view.html', {'playlist': playlist})


def playlist_search_talks(request):
    query = request.GET.get('query')
    return JsonResponse({
        'results': [
            {'id': talk.pk, 'title': talk.title}
            for talk in Talk.objects.filter(title__icontains=query)
        ],
    })


class DownloadView(generic.ListView):
    template_name = 'talk/download.html'
    fields = ['title']
    paginate_by = 500

    def get_queryset(self):
        """Return the last five published questions."""
        self.query = self.request.GET.get('q')
        queryset = Talk.objects.order_by('pk')
        if self.query:
            queryset = queryset.filter(title__icontains=self.query)
        return queryset


def download_transcription(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    return HttpResponse(talk.transcription_text, content_type='text/plain')


def contact(request):
    return render(request, 'contact.html')


@login_required()
def profile_view(request):
    return render(request, 'account/profile.html', {
    })


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
