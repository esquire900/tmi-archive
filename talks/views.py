from django.shortcuts import render
from .models import Talk
from django.views import generic
from django.db.models import Q


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
        context['search_query'] = self.request.GET.get('q')
        return context


class DetailView(generic.DetailView):
    model = Talk
    template_name = 'talk/view.html'


class UpdateView(generic.UpdateView):
    model = Talk
    template_name = 'talk/edit.html'
    fields = ['title', 'description']
