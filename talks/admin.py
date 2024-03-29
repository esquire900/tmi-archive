from django.contrib import admin
from adminsortable2.admin import SortableInlineAdminMixin, SortableAdminBase

from .models import Talk
from .models import Playlist

admin.site.register(Talk)


class TalkTabularInline(SortableInlineAdminMixin, admin.TabularInline):
    model = Playlist.talks.through


@admin.register(Playlist)
class PlaylistAdmin(admin.ModelAdmin, SortableAdminBase):
    inlines = [
        TalkTabularInline,
    ]
