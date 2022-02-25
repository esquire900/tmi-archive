(function() {
  const results = document.createElement('div');

  let searchNumber = 0;
  const performSearch = async (query) => {
    searchNumber += 1;
    const thisSearch = searchNumber;
    if (query.length < 2) {
      results.innerHTML = 'Please enter 2 or more characters';
      return;
    }

    const resp = await fetch(`/playlist/search-talks?query=${encodeURIComponent(query)}`);
    const data = await resp.json();
    if (searchNumber != thisSearch) {
      // Some other search has been triggered; our results are outdated.
      return;
    }
    results.innerHTML = '';
    for (const talk of data.results) {
      if (container.querySelector(`input[value="${talk.id}"]`)) {
        // Already in the list
        return;
      }
      const resultItem = document.createElement('div');
      resultItem.innerText = talk.title;
      resultItem.addEventListener('click', () => {
        const listItem = document.createElement('div');
        listItem.setAttribute('class', 'custom-control custom-checkbox sortable-chosen');
        listItem.setAttribute('draggable', 'true');
        const inputId = `id_talks_added_${talk.id}`;
        listItem.innerHTML = `
          <input type="checkbox" class="custom-control-input" name="talks" value="${talk.id}" id="${inputId}" checked="">
          <label class="custom-control-label" for="${inputId}">
            ${_.escape(talk.title)}
          </label>
        `;
        container.insertBefore(listItem, input);
        resultItem.remove();
      }, false);
      results.append(resultItem);
    }
  };

  const container = document.querySelector('#div_id_talks > div');
  const sortable = Sortable.create(container, {
    filter: '.form-text',
    animation: 150,
  });

  const input = document.createElement('input');
  input.addEventListener('input', _.debounce((event) => {
    performSearch(input.value);
  }, 300), false);

  const helpText = container.querySelector('.form-text');
  container.insertBefore(input, helpText);
  container.insertBefore(results, helpText);
})();
