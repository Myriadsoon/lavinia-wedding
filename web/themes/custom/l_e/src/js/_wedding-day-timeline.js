(function (Drupal, once) {
  Drupal.behaviors.weddingDayTimeline = {
    attach(context) {
      once(
        'wedding-day-timeline',
        '.wedding-day__desktop',
        context
      ).forEach((timeline) => {
        const tabs = Array.from(
          timeline.querySelectorAll(
              '.wedding-day__timeline-event[role="tab"]'
          )
        );

        const panels = Array.from(
          timeline.querySelectorAll(
            '.wedding-day__detail-panel[role="tabpanel"]'
          )
        );

        const activateTab = (tab) => {
          const eventId = tab.dataset.timelineEvent;

          tabs.forEach((item) => {
            const isActive = item === tab;

            item.classList.toggle('is-active', isActive);
            item.setAttribute(
              'aria-selected',
              isActive ? 'true' : 'false'
            );

            item.tabIndex = isActive ? 0 : -1;
          });

          panels.forEach((panel) => {
            const isActive =
              panel.dataset.timelinePanel === eventId;

            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
          });
        };

        const focusTab = (index) => {
          const tab = tabs[index];

          if (!tab) {
            return;
          }

          activateTab(tab);
          tab.focus();
        };

        tabs.forEach((tab, index) => {
          tab.addEventListener('click', () => {
            activateTab(tab);
          });

          tab.addEventListener('keydown', (event) => {
            switch (event.key) {
              case 'ArrowDown':
                event.preventDefault();
                focusTab((index + 1) % tabs.length);
                break;

              case 'ArrowUp':
                event.preventDefault();
                focusTab(
                  (index - 1 + tabs.length) % tabs.length
                );
                break;

              case 'Home':
                event.preventDefault();
                focusTab(0);
                break;

              case 'End':
                event.preventDefault();
                focusTab(tabs.length - 1);
                break;
            }
          });
        });
      });
    }
  };
})(Drupal, once);
