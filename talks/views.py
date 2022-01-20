from django.contrib.auth.decorators import login_required
from django.db.models import Q
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
        query = self.request.GET.get('q')
        if query is None:
            return Talk.objects.all()
        return Talk.objects.filter(
            Q(title__icontains=query)
        ).all()

    def get_context_data(self, **kwargs):
        # Call the base implementation first to get a context
        context = super(IndexView, self).get_context_data(**kwargs)
        # Add in the publisher
        context['search_query'] = self.request.GET.get('q') or ''
        return context


class DetailView(generic.DetailView):
    model = Talk
    template_name = 'talk/view.html'

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        use_original_data = self.request.GET.get('original_audio') == 1

        audio = False
        if not use_original_data and self.get_object().audio_cleaned is not None:
            audio = self.get_object().audio_cleaned
        elif self.get_object().audio_original is not None:
            audio = self.get_object().audio_original
        try:
            if audio is not False:
                audio.url
        except ValueError:
            audio = False
        context['audio'] = audio
        return context


class UpdateView(generic.UpdateView):
    model = Talk
    template_name = 'talk/edit.html'
    form_class = TalkForm


class PlaylistUpdateView(generic.UpdateView):
    model = Playlist
    template_name = 'playlist/edit.html'
    form_class = PlaylistForm


class PlaylistCreateView(generic.UpdateView):
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


def contact(request):
    return render(request, 'contact.html')


@login_required()
def profile_view(request):
    return render(request, 'account/profile.html', {
    })
