from crispy_forms.helper import FormHelper
from crispy_forms.layout import Submit
from django import forms
from tinymce.widgets import TinyMCE

from .models import Talk


class TalkForm(forms.ModelForm):
    description = forms.CharField(widget=TinyMCE(attrs={'cols': 80, 'rows': 30}))

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.helper = FormHelper()
        self.helper.add_input(Submit('submit', 'Submit'))

    class Meta:
        model = Talk
        fields = ['title', 'description']
