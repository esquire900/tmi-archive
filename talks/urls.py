from django.contrib.auth.views import LogoutView
from django.urls import path
from django.views.generic.base import RedirectView
from django.contrib.auth.decorators import login_required
from talks import views
from rest_framework import routers
from django.urls import include, path

router = routers.DefaultRouter()
router.register(r'talks', views.TalkViewSet)
router.register(r'playlists', views.PlaylistViewSet)

urlpatterns = [
    path('', views.IndexView.as_view(), name='talk_index'),
    path('talk/<int:pk>/old-viewer', views.DetailView.as_view(), name='talk_view_old'),

    path('talk/<int:pk>', views.NewDetailView.as_view(), name='talk_view'),
    path('talk/<int:pk>/original-audio', views.NewDetailOriginalView.as_view(), name='talk_view_old'),

    path('talk/<int:pk>/transcription', views.talk_transcription, name='talk_transcription'),

    path('talk/<int:pk>/edit', login_required(views.UpdateView.as_view()), name='talk_edit'),
    path('playlist/', views.playlist_index, name='playlist_index'),
    path('playlist/<int:pk>', views.playlist_view, name='playlist_view'),
    path('playlist/<int:pk>/edit', login_required(views.PlaylistUpdateView.as_view()), name='playlist_edit'),
    path('playlist/create', login_required(views.PlaylistCreateView.as_view()), name='playlist_create'),
    path('playlist/search-talks', login_required(views.playlist_search_talks), name='playlist_search_talks'),

    path('accounts/profile/', views.profile_view, name='profile_view'),
    path('contact', views.contact, name='contact'),
    path('download', views.DownloadView.as_view(), name='download'),
    path('download-transcription/<int:pk>', views.download_transcription, name='download_transcription'),
    path('api/v1/', include(router.urls)),
    path('api/v1/api-auth/', include('rest_framework.urls', namespace='rest_framework'))
]
