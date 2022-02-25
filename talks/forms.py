from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from django import forms
from tinymce.widgets import TinyMCE
from .models import Talk, Playlist


class TalkForm(forms.ModelForm):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', 'Submit'))

    class Meta:
        model = Talk
        fields = ['title', 'description', 'transcription']


class SortableTalksField(forms.ModelMultipleChoiceField):
    def clean(self, value):
        # First, call the default implementation, to get a queryset of talks.
        queryset = super().clean(value)
        # If no error was raised, reconstruct the order from the form value.
        ordering = {pk: index for index, pk in enumerate(value)}
        # Sort the queryset and return a list of talks.
        return sorted(list(queryset), key=lambda item: ordering[str(item.pk)])


class PlaylistForm(forms.ModelForm):
    description = forms.CharField(widget=TinyMCE(attrs={'cols': 80, 'rows': 10}))
    first_recording_date = forms.DateInput(
        # =
    )
    talks = SortableTalksField(
        queryset=Talk.objects.all(),
        widget=forms.CheckboxSelectMultiple(),
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
