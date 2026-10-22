const PAGE_SIZE = 6;

async function loadJson(path) {
  const response = await fetch(path);
  if (!response.ok) {
    throw new Error(`Unable to load ${path}: ${response.status}`);
  }
  return response.json();
}

function setStatus(message) {
  const statusNode = document.getElementById('status');
  if (statusNode) {
    statusNode.textContent = message;
  }
}

function getPageData() {
  const results = document.getElementById('results');
  const hasEventControls = !!document.getElementById('categoryFilter') && !!document.getElementById('countryFilter');
  const hasFaqControls = !!document.getElementById('categoryFilter') && !hasEventControls;

  return { results, hasEventControls, hasFaqControls };
}

function normalizeText(value) {
  return String(value ?? '').trim().toLowerCase();
}

function createSelectOptions(select, values) {
  if (!select) return;
  const existing = new Set(Array.from(select.options).map((option) => option.value));
  values
    .filter((value) => value && !existing.has(value))
    .forEach((value) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = value;
      select.appendChild(option);
    });
}

async function initEventsPage() {
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const sortSelect = document.getElementById('sortSelect');
  const countryFilter = document.getElementById('countryFilter');
  const stateFilter = document.getElementById('stateFilter');
  const cityFilter = document.getElementById('cityFilter');
  const resetButton = document.getElementById('resetButton');
  const resultsNode = document.getElementById('results');
  const pageInfo = document.getElementById('pageInfo');
  const previousButton = document.getElementById('previousButton');
  const nextButton = document.getElementById('nextButton');

  if (!searchInput || !resultsNode) return;

  let allEvents = [];
  let page = 1;

  const buildFilterOptions = (items) => {
    const categories = [...new Set(items.map((item) => item.category).filter(Boolean))].sort();
    createSelectOptions(categoryFilter, ['all', ...categories]);
  };

  try {
    setStatus('Loading events...');
    const data = await loadJson('../json/events.json');
    allEvents = Array.isArray(data) ? data : [];
    buildFilterOptions(allEvents);
    setStatus(`${allEvents.length} events loaded.`);
    render();
  } catch (error) {
    setStatus('Unable to load events. Please refresh the page.');
    resultsNode.innerHTML = '<p class="status">No event data available.</p>';
    console.error(error);
    return;
  }

  function getVisibleEvents() {
    const searchText = normalizeText(searchInput.value);
    const categoryValue = categoryFilter ? categoryFilter.value : 'all';
    const sortValue = sortSelect ? sortSelect.value : 'date-asc';
    const matches = allEvents.filter((event) => {
      const matchesText = !searchText || [event.title, event.location, event.description, event.category].some((value) => normalizeText(value).includes(searchText));
      const matchesCategory = categoryValue === 'all' || event.category === categoryValue;
      return matchesText && matchesCategory;
    });

    const sorted = [...matches].sort((a, b) => {
      switch (sortValue) {
        case 'title-desc':
          return String(b.title).localeCompare(String(a.title));
        case 'title-asc':
          return String(a.title).localeCompare(String(b.title));
        case 'date-desc':
          return new Date(b.date) - new Date(a.date);
        default:
          return new Date(a.date) - new Date(b.date);
      }
    });

    return sorted;
  }

  function render() {
    const filteredEvents = getVisibleEvents();
    const totalPages = Math.max(1, Math.ceil(filteredEvents.length / PAGE_SIZE));
    page = Math.min(page, totalPages);

    const start = (page - 1) * PAGE_SIZE;
    const pageItems = filteredEvents.slice(start, start + PAGE_SIZE);

    if (!pageItems.length) {
      resultsNode.innerHTML = '<article class="event-card"><h2>No matching events found</h2><p>Try a different search or reset the filters.</p></article>';
    } else {
      resultsNode.innerHTML = pageItems.map((event) => `
        <article class="event-card">
          <div class="category">${event.category || 'General'}</div>
          <h2>${event.title}</h2>
          <p class="event-meta"><strong>Date:</strong> ${event.date}</p>
          <p class="event-meta"><strong>Location:</strong> ${event.location}</p>
          <p>${event.description}</p>
        </article>
      `).join('');
    }

    if (pageInfo) pageInfo.textContent = `Page ${page} of ${totalPages}`;
    if (previousButton) previousButton.disabled = page === 1;
    if (nextButton) nextButton.disabled = page >= totalPages;
  }

  searchInput.addEventListener('input', () => {
    page = 1;
    render();
  });

  categoryFilter?.addEventListener('change', () => {
    page = 1;
    render();
  });

  sortSelect?.addEventListener('change', () => {
    page = 1;
    render();
  });

  countryFilter?.addEventListener('change', () => {
    page = 1;
    render();
  });

  stateFilter?.addEventListener('change', () => {
    page = 1;
    render();
  });

  cityFilter?.addEventListener('change', () => {
    page = 1;
    render();
  });

  previousButton?.addEventListener('click', () => {
    if (page > 1) {
      page -= 1;
      render();
    }
  });

  nextButton?.addEventListener('click', () => {
    const filteredEvents = getVisibleEvents();
    const totalPages = Math.max(1, Math.ceil(filteredEvents.length / PAGE_SIZE));
    if (page < totalPages) {
      page += 1;
      render();
    }
  });

  resetButton?.addEventListener('click', () => {
    searchInput.value = '';
    categoryFilter.value = 'all';
    sortSelect.value = 'date-asc';
    if (countryFilter) countryFilter.value = 'all';
    if (stateFilter) stateFilter.value = 'all';
    if (cityFilter) cityFilter.value = 'all';
    page = 1;
    render();
  });
}

async function initFaqPage() {
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const sortSelect = document.getElementById('sortSelect');
  const resetButton = document.getElementById('resetButton');
  const resultsNode = document.getElementById('results');
  const pageInfo = document.getElementById('pageInfo');
  const previousButton = document.getElementById('previousButton');
  const nextButton = document.getElementById('nextButton');

  if (!searchInput || !resultsNode) return;

  let allFaqs = [];
  let page = 1;

  const buildFilterOptions = (items) => {
    const categories = [...new Set(items.map((item) => item.category).filter(Boolean))].sort();
    createSelectOptions(categoryFilter, ['all', ...categories]);
  };

  try {
    setStatus('Loading FAQs...');
    const data = await loadJson('../json/faqs.json');
    allFaqs = Array.isArray(data) ? data : [];
    buildFilterOptions(allFaqs);
    setStatus(`${allFaqs.length} FAQs loaded.`);
    render();
  } catch (error) {
    setStatus('Unable to load FAQs. Please try again later.');
    resultsNode.innerHTML = '<div class="faq-card"><h2>No FAQs available</h2><p>Your FAQ data could not be loaded.</p></div>';
    console.error(error);
    return;
  }

  function getVisibleFaqs() {
    const searchText = normalizeText(searchInput.value);
    const categoryValue = categoryFilter.value;
    const sortValue = sortSelect.value;

    const filtered = allFaqs.filter((faq) => {
      const matchesText = !searchText || [faq.question, faq.answer, faq.category].some((value) => normalizeText(value).includes(searchText));
      const matchesCategory = categoryValue === 'all' || faq.category === categoryValue;
      return matchesText && matchesCategory;
    });

    return [...filtered].sort((a, b) => {
      if (sortValue === 'question-desc') return String(b.question).localeCompare(String(a.question));
      if (sortValue === 'category-asc') return String(a.category).localeCompare(String(b.category));
      return String(a.question).localeCompare(String(b.question));
    });
  }

  function render() {
    const visibleFaqs = getVisibleFaqs();
    const totalPages = Math.max(1, Math.ceil(visibleFaqs.length / PAGE_SIZE));
    page = Math.min(page, totalPages);
    const start = (page - 1) * PAGE_SIZE;
    const pageItems = visibleFaqs.slice(start, start + PAGE_SIZE);

    if (!pageItems.length) {
      resultsNode.innerHTML = '<article class="faq-card"><h2>No matching FAQs</h2><p>Try a different keyword or category.</p></article>';
    } else {
      resultsNode.innerHTML = pageItems.map((faq) => `
        <article class="faq-card">
          <div class="category">${faq.category || 'General'}</div>
          <h2>${faq.question}</h2>
          <p>${faq.answer}</p>
        </article>
      `).join('');
    }

    pageInfo.textContent = `Page ${page} of ${totalPages}`;
    previousButton.disabled = page === 1;
    nextButton.disabled = page >= totalPages;
  }

  searchInput.addEventListener('input', () => {
    page = 1;
    render();
  });

  categoryFilter.addEventListener('change', () => {
    page = 1;
    render();
  });

  sortSelect.addEventListener('change', () => {
    page = 1;
    render();
  });

  previousButton.addEventListener('click', () => {
    if (page > 1) {
      page -= 1;
      render();
    }
  });

  nextButton.addEventListener('click', () => {
    const visibleFaqs = getVisibleFaqs();
    const totalPages = Math.max(1, Math.ceil(visibleFaqs.length / PAGE_SIZE));
    if (page < totalPages) {
      page += 1;
      render();
    }
  });

  resetButton.addEventListener('click', () => {
    searchInput.value = '';
    categoryFilter.value = 'all';
    sortSelect.value = 'question-asc';
    page = 1;
    render();
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const hasEventControls = !!document.getElementById('searchInput') && !!document.getElementById('countryFilter');
  const hasFaqControls = !!document.getElementById('searchInput') && !!document.getElementById('categoryFilter') && !hasEventControls;

  if (hasEventControls) {
    initEventsPage();
  } else if (hasFaqControls) {
    initFaqPage();
  }
});
