from django.contrib.auth.decorators import login_required
from django.db.models import Q
from django.http import HttpResponse
from django.shortcuts import render
from django.shortcuts import get_object_or_404
from django.views import generic
from django.contrib.auth.decorators import login_required

from .forms import TalkForm, PlaylistForm
from .models import Talk
from .models import Playlist


class IndexView(generic.ListView):
    template_name = 'talk/list.html'
    fields = ['title']
    paginate_by = 10

    # context_object_name = 'latest_question_list'

    def get_queryset(self):
        """Return the last five published questions."""
        self.query = self.request.GET.get('q')
        queryset = Talk.objects.order_by('pk')
        if self.query:
            queryset = queryset.filter(title__icontains=self.query)
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


class NewDetailView(DetailView):
    model = Talk
    template_name = 'talk/view_new.html'


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


def download(request):
    talks = Talk.objects.all()
    return render(request, 'download.html', {'talks': talks})


def download_data(request):
    from django.http import JsonResponse

    data = {}
    talks = Talk.objects.all()

    for talk in talks:
        data[talk.id] = {
            'id': talk.id,
            'title': talk.title,
            'audio_url': talk.mp3_url_clean,
        }
    return JsonResponse(data)


def download_transcription(request, pk):
    talk = get_object_or_404(Talk, pk=pk)
    return HttpResponse(talk.transcription_text, content_type='text/plain')


def contact(request):
    return render(request, 'contact.html')


@login_required()
def profile_view(request):
    return render(request, 'account/profile.html', {
    })
