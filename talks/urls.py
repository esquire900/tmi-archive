from django.contrib.auth.decorators import login_required
from talks import views, api_views
from rest_framework import routers
from django.urls import include, path, re_path

router = routers.DefaultRouter()
router.register(r'talks', api_views.TalkViewSet)
router.register(r'playlists', api_views.PlaylistViewSet)

urlpatterns = [
    path('', views.site_index, name='site_index'),

    path('talk', views.IndexView.as_view(), name='talk_index'),
    path('talk/<int:pk>', views.DetailView.as_view(), name='talk_view'),
    path('talk/<int:pk>/original-audio', views.DetailOriginalView.as_view(), name='talk_view_original'),
    path('talk/<int:pk>/edit', login_required(views.UpdateView.as_view()), name='talk_edit'),

    path('playlist/', views.playlist_index, name='playlist_index'),
    path('playlist/<int:pk>', views.playlist_view, name='playlist_view'),
    path('playlist/<int:pk>/edit', login_required(views.PlaylistUpdateView.as_view()), name='playlist_edit'),
    path('playlist/create', login_required(views.PlaylistCreateView.as_view()), name='playlist_create'),
    path('playlist/search-talks', login_required(views.playlist_search_talks), name='playlist_search_talks'),

    path('accounts/profile/', views.profile_view, name='profile_view'),
    path('contact', views.contact, name='contact'),
    path('bulk-download', views.BulkDownloadView.as_view(), name='download-bulk'),
    path('robots.txt', views.robots_txt, name='robots-txt'),

    path('api/v1/', include(router.urls)),
    path('api/v1/api-auth/', include('rest_framework.urls', namespace='rest_framework')),
    path('api/v1/talks/<int:pk>/transcription', api_views.talk_transcription, name='talk_transcription'),
    path('api/v1/talks/<int:pk>/download', api_views.download_audio, name='talk_download'),
    path('api/v1/talks/<int:pk>/download/<str:audio_type>', api_views.download_audio, name='talk_download_original'),

]
