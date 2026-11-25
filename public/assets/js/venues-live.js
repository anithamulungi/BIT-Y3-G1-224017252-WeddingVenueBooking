(() => {
  const search = document.querySelector('#venue-search');
  const grid = document.querySelector('#venue-grid');
  const count = document.querySelector('#venue-count');
  const status = document.querySelector('#async-status');
  if (!search || !grid) return;

  let controller;
  let timer;
  const text = (tag, className, value) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    node.textContent = value;
    return node;
  };

  const render = (venues) => {
    grid.replaceChildren();
    for (const venue of venues) {
      const card = document.createElement('article');
      card.className = 'card venue-card';
      card.append(text('span', 'eyebrow', 'Wedding venue'));
      card.append(text('h2', '', venue.name));
      card.append(text('p', 'venue-location', venue.location));
      card.append(text('p', 'venue-description', venue.description));
      const details = document.createElement('p');
      details.className = 'meta';
      details.append(text('span', '', `${venue.capacity} guests`));
      details.append(text('span', '', `From ${Number(venue.price_per_day).toLocaleString('en-RW')} RWF / day`));
      card.append(details);
      const link = document.createElement('a');
      link.className = 'button';
      link.href = `${window.WEDDING_BOOKING}&venue_id=${encodeURIComponent(venue.venue_id)}`;
      link.textContent = 'Reserve this venue';
      card.append(link);
      grid.append(card);
    }
    count.textContent = String(venues.length);
    if (!venues.length) grid.append(text('p', 'empty-state', 'No venues match your search. Try another location or name.'));
  };

  search.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      if (controller) controller.abort();
      controller = new AbortController();
      status.textContent = '';
      try {
        const url = `${window.WEDDING_API}&q=${encodeURIComponent(search.value.trim())}`;
        const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
        if (!response.ok) throw new Error('Venue search is temporarily unavailable.');
        const payload = await response.json();
        if (!payload.ok || !Array.isArray(payload.data)) throw new Error('We could not load venue results.');
        render(payload.data);
      } catch (error) {
        if (error.name !== 'AbortError') status.textContent = error.message;
      }
    }, 220);
  });
})();
