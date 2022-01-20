from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from django import forms
from tinymce.widgets import TinyMCE
from django_select2.forms import ModelSelect2MultipleWidget
from .models import Talk, Playlist


class TalkForm(forms.ModelForm):
    description = forms.CharField(widget=TinyMCE(attrs={'cols': 80, 'rows': 30}))

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', 'Submit'))

    class Meta:
        model = Talk
        fields = ['title', 'description']


class PlaylistForm(forms.ModelForm):
    description = forms.CharField(widget=TinyMCE(attrs={'cols': 80, 'rows': 10}))
    first_recording_date = forms.DateInput(
        # =
    )
    talks = forms.ModelMultipleChoiceField(
        queryset=Talk.objects.all(),
        widget=ModelSelect2MultipleWidget(
            search_fields=['title__icontains']
        ),
        help_text='Which talks to include in the playlist. Select talks by searching in the field above. Talks are not sortable at the moment.'
    )

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', 'Submit'))

    class Meta:
        model = Playlist
        fields = ['title', 'description', 'first_recording_date', 'talks']
        help_texts = {
            'first_recording_date': 'Approximate date of first recording',
        }
