from django.contrib.auth.views import LogoutView
from django.urls import path
from django.views.generic.base import RedirectView

from talks import views

urlpatterns = [
    path('', views.IndexView.as_view(), name='talk_index'),
    path('talk/<int:pk>', views.DetailView.as_view(), name='talk_view'),
    path('talk/<int:pk>/edit', views.UpdateView.as_view(), name='talk_edit'),
    path('playlist/', views.playlist_index, name='playlist_index'),
    path('playlist/<int:pk>', views.playlist_view, name='playlist_view'),
    path('playlist/<int:pk>/edit', views.PlaylistUpdateView.as_view(), name='playlist_edit'),
    path('playlist/create', views.PlaylistCreateView.as_view(), name='playlist_create'),

    path('accounts/profile/', views.profile_view, name='profile_view'),
    path('contact', views.contact, name='contact')

]
