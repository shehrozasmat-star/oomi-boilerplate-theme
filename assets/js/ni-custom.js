window.onload = function () {
  const titles = document.querySelectorAll('.fv-title-underline div h3'); // Get all card titles
  
  titles.forEach(title => {
    const titleText = title.innerHTML;

    // Split the title into individual words and wrap each word in a span
    const words = titleText.split(' ').map(word => `<span class="word">${word}</span>`).join(' ');
    title.innerHTML = words; // Set the new title with wrapped words in spans

    // Now, check each word to see if it has wrapped onto a new line
    const spans = title.querySelectorAll('.word'); // Get all the span elements (words)

    let wrappedWordsStartIndex = -1;
    let wrappedWordsEndIndex = -1;

    spans.forEach((span, index) => {
      // Get the current position of the word
      const rect = span.getBoundingClientRect();
      const previousRect = spans[index - 1] ? spans[index - 1].getBoundingClientRect() : null;

      // Check if the current word has wrapped by comparing the top position of the word
      if (previousRect && rect.top !== previousRect.top) {
        // If the current span is on a different vertical position than the previous one, it's a new line
        if (wrappedWordsStartIndex === -1) {
          wrappedWordsStartIndex = index; // Mark the start of the wrapped line
        }
        wrappedWordsEndIndex = index - 1; // Mark the last word on the previous line
      }

      // If we have already detected a wrap, underline all the words from this point on
      if (wrappedWordsStartIndex !== -1 && index >= wrappedWordsStartIndex) {
        span.classList.add('fv-card-title-underline'); // Add underline to wrapped words
      }
    });

    // Only add div if wrapped words were detected
    if (wrappedWordsStartIndex !== -1) {
      const divWrapper = document.createElement('div');
      divWrapper.classList.add('fv-card-title-underline-wrapper');
      
      // Append the wrapped words to the div, including spaces
      for (let i = wrappedWordsStartIndex; i < spans.length; i++) {
        divWrapper.appendChild(spans[i]);

        // Append space if it's not the last word in the wrapped line
        if (i < spans.length - 1) {
          divWrapper.appendChild(document.createTextNode(' ')); // Append a space between words
        }
      }

      // Insert the div after the last word before the wrapping started
      const lastWordBeforeWrap = spans[wrappedWordsStartIndex - 1];
      lastWordBeforeWrap.parentElement.insertBefore(divWrapper, lastWordBeforeWrap.nextSibling);
    }
  });
};

const observer = new MutationObserver(mutations => {
  mutations.forEach(mutation => {
    if (mutation.attributeName === "class") {
      const target = mutation.target;

      if (target.classList.contains("e-n-menu-content") && target.classList.contains("e-active")) {
        const wrapper = target.previousElementSibling;
        if (wrapper && wrapper.classList.contains("e-click")) {
          wrapper.classList.add("f-active-class");
        }
      }

      // Remove class if e-active is removed
      if (target.classList.contains("e-n-menu-content") && !target.classList.contains("e-active")) {
        const wrapper = target.previousElementSibling;
        if (wrapper && wrapper.classList.contains("e-click")) {
          wrapper.classList.remove("f-active-class");
        }
      }
    }
  });
});

// Observe all `.e-n-menu-content` elements
document.querySelectorAll(".e-n-menu-content").forEach(el => {
  observer.observe(el, {
    attributes: true,
    attributeFilter: ["class"]
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const filterButtons = document.querySelectorAll('#year-filters span');
  const contentContainer = document.getElementById('navigator-content');

  function setActiveButton(year) {
    filterButtons.forEach(btn => {
      btn.classList.remove('active');
      if (btn.dataset.year === year) {
        btn.classList.add('active');
      }
    });
  }

  function loadNavigatorContent(year) {
    setActiveButton(year);
    
    fetch(`/wp-admin/admin-ajax.php?action=filter_navigator_children&year=${year}`)
      .then(res => res.text())
      .then(html => {
        contentContainer.innerHTML = html;
      });
  }

  // Click event
  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const year = btn.dataset.year;
      loadNavigatorContent(year);

      // Optionally update the URL without reloading
      const url = new URL(window.location);
      url.searchParams.set('year', year);
      window.history.pushState({}, '', url);
    });
  });

  // On page load: auto-load based on ?year param or default to latest year
  const urlParams = new URLSearchParams(window.location.search);
  const currentYear = urlParams.get('year') || '2025'; // Default year fallback
  loadNavigatorContent(currentYear);
});



document.addEventListener('DOMContentLoaded', function () {
  const filterButtons = document.querySelectorAll('#year-filters-seaways span');
  const contentContainer = document.getElementById('seaways-content');

  function setActiveButton(year) {
    filterButtons.forEach(btn => {
      btn.classList.remove('active');
      if (btn.dataset.year === year) {
        btn.classList.add('active');
      }
    });
  }

  function loadSeawaysContent(year) {
    setActiveButton(year);
    
    fetch(`/wp-admin/admin-ajax.php?action=filter_seaways_children&year=${year}`)
      .then(res => res.text())
      .then(html => {
        contentContainer.innerHTML = html;
      });
  }

  // Click event
  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const year = btn.dataset.year;
      loadSeawaysContent(year);

      // Optionally update the URL without reloading
      const url = new URL(window.location);
      url.searchParams.set('year', year);
      window.history.pushState({}, '', url);
    });
  });

  // On page load: auto-load based on ?year param or default to latest year
  const urlParams = new URLSearchParams(window.location.search);
  const currentYear = urlParams.get('year') || '2025'; // Default year fallback
  loadSeawaysContent(currentYear);
});


