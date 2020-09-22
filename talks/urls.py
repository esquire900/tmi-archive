from django.contrib.auth.views import LogoutView
from django.urls import path
from django.views.generic.base import RedirectView

from talks import views

urlpatterns = [
    path('', views.IndexView.as_view(), name='talk_index'),
    path('talk/<int:pk>', views.DetailView.as_view(), name='talk_view'),
    path('talk/<int:pk>/edit', views.UpdateView.as_view(), name='talk_edit'),
    # path('talk/')
]
