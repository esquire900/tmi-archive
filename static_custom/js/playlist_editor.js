(function() {
  const resultsBox = document.createElement('div');
  resultsBox.setAttribute('class', 'nosort card d-none');
  resultsBox.innerHTML = '<div class="card-body"><div class="card-text">';
  results = resultsBox.querySelector('.card-text');

  let searchNumber = 0;
  const performSearch = async (query) => {
    searchNumber += 1;
    const thisSearch = searchNumber;

    resultsBox.classList.remove('d-none');

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

    if (! data.results.length) {
      results.innerHTML = '<em>No results</em>';
      return;
    }

    results.innerHTML = '';
    const ul = document.createElement('ul');
    ul.setAttribute('class', 'mb-0');
    results.append(ul);
    for (const talk of data.results) {
      if (container.querySelector(`input[value="${talk.id}"]`)) {
        // Already in the list
        return;
      }
      const resultItem = document.createElement('li');
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
      ul.append(resultItem);
    }
  };

  const container = document.querySelector('#div_id_talks > div');
  const sortable = Sortable.create(container, {
    filter: '.nosort',
    animation: 150,
  });

  const input = document.createElement('input');
  input.addEventListener('input', _.debounce((event) => {
    performSearch(input.value);
  }, 300), false);

  const helpText = container.querySelector('.form-text');
  helpText.classList.add('nosort');
  container.insertBefore(input, helpText);
  container.insertBefore(resultsBox, helpText);
})();
