/**
 * Relationship Headcanon Generator - Vanilla JS Engine
 */

(function () {
  'use strict';

  // Sample archetypes for the "Random Archetype" button
  var ARCHETYPES = [
    ['Grumpy', 'Sunshine'],
    ['Hero', 'Villain'],
    ['Golden Retriever', 'Black Cat'],
    ['Detective', 'Master Thief'],
    ['Academic Rival', 'Class Clown'],
    ['Knight', 'Monarch'],
    ['Immortal God', 'Chaotic Mortal'],
    ['Coffee Addict', 'Tea Enthusiast'],
    ['Overthinker', 'Go With The Flow'],
    ['Bodyguard', 'Client']
  ];

  function initRHG(wrapper) {
    var personAInput     = wrapper.querySelector('.rhg-input-a');
    var personBInput     = wrapper.querySelector('.rhg-input-b');
    var swapBtn          = wrapper.querySelector('.rhg-btn-swap');
    var categorySelect   = wrapper.querySelector('.rhg-category-select');
    var countSelect      = wrapper.querySelector('.rhg-count-select');
    var randomPairBtn    = wrapper.querySelector('.rhg-btn-random-pair');
    var generateBtn      = wrapper.querySelector('.rhg-btn-generate');
    var outputArea       = wrapper.querySelector('.rhg-output-area');
    var outputList       = wrapper.querySelector('.rhg-output-list');
    var emptyState       = wrapper.querySelector('.rhg-empty-state');
    var favToggleBtn     = wrapper.querySelector('.rhg-btn-favorites-toggle');
    var favCountBadge    = wrapper.querySelector('.rhg-fav-count');
    var favModal         = wrapper.querySelector('.rhg-favorites-modal');
    var favList          = wrapper.querySelector('.rhg-favorites-list');
    var clearFavsBtn     = wrapper.querySelector('.rhg-btn-clear-favs');
    var embedToggleBtn   = wrapper.querySelector('.rhg-btn-embed-toggle');
    var embedModal       = wrapper.querySelector('.rhg-embed-modal');
    var copyEmbedBtn     = wrapper.querySelector('.rhg-btn-copy-embed');
    var themeToggleBtn   = wrapper.querySelector('.rhg-theme-toggle');

    var data       = window.rhgData || { prompts: [], categories: {}, settings: {}, i18n: {} };
    var prompts    = data.prompts || [];
    var categories = data.categories || {};
    var settings   = data.settings || {};
    var i18n       = data.i18n || {};

    var lastUsedPromptIds = [];
    var STORAGE_FAVS_KEY = 'rhg_user_favorites';
    var STORAGE_THEME_KEY = 'rhg_user_theme';

    // Initialize Theme
    var savedTheme = localStorage.getItem(STORAGE_THEME_KEY);
    if (savedTheme === 'dark') {
      wrapper.classList.add('rhg-dark-mode');
    }

    if (themeToggleBtn) {
      themeToggleBtn.addEventListener('click', function () {
        wrapper.classList.toggle('rhg-dark-mode');
        var isDark = wrapper.classList.contains('rhg-dark-mode');
        localStorage.setItem(STORAGE_THEME_KEY, isDark ? 'dark' : 'light');
      });
    }

    // Clear buttons for inputs
    wrapper.querySelectorAll('.rhg-btn-clear').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var input = this.parentElement.querySelector('input');
        if (input) {
          input.value = '';
          input.focus();
        }
      });
    });

    // Swap button
    if (swapBtn) {
      swapBtn.addEventListener('click', function () {
        var valA = personAInput.value;
        var valB = personBInput.value;
        personAInput.value = valB;
        personBInput.value = valA;
      });
    }

    // Random Archetype Button
    if (randomPairBtn) {
      randomPairBtn.addEventListener('click', function () {
        var pair = ARCHETYPES[Math.floor(Math.random() * ARCHETYPES.length)];
        personAInput.value = pair[0];
        personBInput.value = pair[1];
        showToast('Selected: ' + pair[0] + ' & ' + pair[1]);
      });
    }

    // Favorites Helper
    function getFavorites() {
      try {
        return JSON.parse(localStorage.getItem(STORAGE_FAVS_KEY)) || [];
      } catch (e) {
        return [];
      }
    }

    function saveFavorites(favs) {
      try {
        localStorage.setItem(STORAGE_FAVS_KEY, JSON.stringify(favs));
        updateFavCount();
      } catch (e) {}
    }

    function updateFavCount() {
      if (favCountBadge) {
        favCountBadge.textContent = getFavorites().length;
      }
    }
    updateFavCount();

    // Toast notification
    function showToast(message) {
      var existing = document.querySelector('.rhg-toast');
      if (existing) {
        existing.remove();
      }
      var toast = document.createElement('div');
      toast.className = 'rhg-toast';
      toast.textContent = message;
      document.body.appendChild(toast);
      setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s ease';
        setTimeout(function () {
          toast.remove();
        }, 300);
      }, 2500);
    }

    // Interpolate text template
    function formatPromptText(template, nameA, nameB, highlight) {
      var cleanA = nameA || personAInput.getAttribute('placeholder') || 'Person A';
      var cleanB = nameB || personBInput.getAttribute('placeholder') || 'Person B';

      var displayA = highlight ? '<mark>' + escapeHtml(cleanA) + '</mark>' : cleanA;
      var displayB = highlight ? '<mark>' + escapeHtml(cleanB) + '</mark>' : cleanB;

      var text = template
        .replace(/\{name1\}/g, displayA)
        .replace(/\{name2\}/g, displayB);

      return text;
    }

    function escapeHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    // Generate action
    function generateHeadcanons() {
      var nameA = personAInput.value.trim() || personAInput.getAttribute('placeholder') || 'Person A';
      var nameB = personBInput.value.trim() || personBInput.getAttribute('placeholder') || 'Person B';
      var selectedCat = categorySelect ? categorySelect.value : 'all';
      var count = countSelect ? parseInt(countSelect.value, 10) : 1;

      // Filter available prompts
      var filtered = prompts.filter(function (item) {
        if (selectedCat === 'all' || !selectedCat) return true;
        return item.category === selectedCat;
      });

      if (!filtered.length) {
        filtered = prompts; // fallback
      }

      // Filter out recent prompts if possible to keep it fresh
      var pool = filtered.filter(function (item) {
        return lastUsedPromptIds.indexOf(item.id) === -1;
      });
      if (pool.length < count) {
        pool = filtered;
        lastUsedPromptIds = [];
      }

      // Shuffle pool
      var shuffled = pool.slice().sort(function () {
        return 0.5 - Math.random();
      });

      var selected = shuffled.slice(0, count);

      // Render cards
      outputList.innerHTML = '';
      if (emptyState) emptyState.style.display = 'none';
      if (outputArea) outputArea.style.display = 'block';

      selected.forEach(function (promptItem) {
        lastUsedPromptIds.push(promptItem.id);
        var rawText = formatPromptText(promptItem.text, nameA, nameB, false);
        var markedText = formatPromptText(promptItem.text, nameA, nameB, true);
        var catInfo = categories[promptItem.category] || { name: promptItem.category, icon: '✨' };

        var card = document.createElement('div');
        card.className = 'rhg-result-card';
        card.setAttribute('data-id', promptItem.id);

        var isFav = getFavorites().some(function (f) {
          return f.rawText === rawText;
        });

        card.innerHTML =
          '<div class="rhg-card-top">' +
            '<span class="rhg-card-badge">' + (catInfo.icon || '✨') + ' ' + (catInfo.name || 'Headcanon') + '</span>' +
            '<button type="button" class="rhg-card-fav-btn ' + (isFav ? 'is-active' : '') + '" title="Favorite" aria-label="Favorite">★</button>' +
          '</div>' +
          '<p class="rhg-card-text">' + markedText + '</p>' +
          '<div class="rhg-card-actions">' +
            '<button type="button" class="rhg-action-btn rhg-btn-copy">📋 Copy</button>' +
            '<button type="button" class="rhg-action-btn rhg-btn-image">🖼️ Save Card</button>' +
            '<button type="button" class="rhg-action-btn rhg-btn-share-x">𝕏 Post</button>' +
            '<button type="button" class="rhg-action-btn rhg-btn-share-tumblr">Tumblr</button>' +
            '<button type="button" class="rhg-action-btn rhg-btn-share-generic">Share</button>' +
          '</div>';

        // Favorite Toggle
        var favBtn = card.querySelector('.rhg-card-fav-btn');
        favBtn.addEventListener('click', function () {
          var favs = getFavorites();
          var index = favs.findIndex(function (f) { return f.rawText === rawText; });
          if (index > -1) {
            favs.splice(index, 1);
            favBtn.classList.remove('is-active');
            showToast(i18n.removed || 'Removed from favorites.');
          } else {
            favs.push({
              id: promptItem.id,
              category: promptItem.category,
              categoryName: catInfo.name,
              rawText: rawText,
              names: nameA + ' x ' + nameB,
              date: new Date().toLocaleDateString()
            });
            favBtn.classList.add('is-active');
            showToast(i18n.saved || 'Saved to favorites!');
          }
          saveFavorites(favs);
        });

        // Copy button
        card.querySelector('.rhg-btn-copy').addEventListener('click', function () {
          var copyPayload = rawText + '\n\n— (' + nameA + ' x ' + nameB + ')\nGenerated via: ' + (settings.backlinkUrl || window.location.href);
          if (navigator.clipboard) {
            navigator.clipboard.writeText(copyPayload).then(function () {
              showToast(i18n.copied || 'Copied to clipboard!');
            });
          } else {
            var temp = document.createElement('textarea');
            temp.value = copyPayload;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            temp.remove();
            showToast(i18n.copied || 'Copied to clipboard!');
          }
        });

        // Share X / Twitter
        card.querySelector('.rhg-btn-share-x').addEventListener('click', function () {
          var tweet = encodeURIComponent('"' + rawText + '"\n\n(' + nameA + ' x ' + nameB + ') #OTP #Headcanon\n' + (settings.backlinkUrl || window.location.href));
          window.open('https://twitter.com/intent/tweet?text=' + tweet, '_blank', 'width=600,height=400');
        });

        // Share Tumblr
        card.querySelector('.rhg-btn-share-tumblr').addEventListener('click', function () {
          var tUrl = 'https://www.tumblr.com/widgets/share/tool?posttype=quote&content=' +
            encodeURIComponent(rawText) +
            '&source=' + encodeURIComponent(nameA + ' x ' + nameB + ' | ' + (settings.backlinkText || 'Headcanon Generator')) +
            '&canonicalUrl=' + encodeURIComponent(settings.backlinkUrl || window.location.href);
          window.open(tUrl, '_blank', 'width=600,height=500');
        });

        // Generic Share / WhatsApp
        card.querySelector('.rhg-btn-share-generic').addEventListener('click', function () {
          var shareData = {
            title: (i18n.shareTitle || 'OTP Headcanon: ') + nameA + ' x ' + nameB,
            text: rawText,
            url: settings.backlinkUrl || window.location.href
          };
          if (navigator.share) {
            navigator.share(shareData).catch(function () {});
          } else {
            var waUrl = 'https://api.whatsapp.com/send?text=' + encodeURIComponent('"' + rawText + '" - ' + shareData.url);
            window.open(waUrl, '_blank');
          }
        });

        // Save as Canvas Image Card
        card.querySelector('.rhg-btn-image').addEventListener('click', function () {
          generateImageCard(rawText, catInfo.name, nameA, nameB);
        });

        outputList.appendChild(card);
      });

      // Smooth scroll to output on mobile
      if (window.innerWidth < 768) {
        outputArea.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }

    // HTML5 Canvas Card Generator (No heavy libraries needed!)
    function generateImageCard(text, categoryName, nameA, nameB) {
      var canvas = document.createElement('canvas');
      var ctx = canvas.getContext('2d');
      var width = 1080;
      var height = 1080;
      canvas.width = width;
      canvas.height = height;

      // Draw Gradient Background
      var gradient = ctx.createLinearGradient(0, 0, width, height);
      gradient.addColorStop(0, '#111827');
      gradient.addColorStop(0.5, '#1e1b4b');
      gradient.addColorStop(1, '#0f172a');
      ctx.fillStyle = gradient;
      ctx.fillRect(0, 0, width, height);

      // Card Inner Glow Border
      ctx.strokeStyle = 'rgba(255, 75, 114, 0.4)';
      ctx.lineWidth = 4;
      ctx.strokeRect(50, 50, width - 100, height - 100);

      // Category Pill
      ctx.fillStyle = '#ff4b72';
      ctx.font = 'bold 30px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText((categoryName || 'HEADCANON').toUpperCase(), width / 2, 140);

      // Ship Title
      ctx.fillStyle = '#ffffff';
      ctx.font = 'bold 44px sans-serif';
      ctx.fillText(nameA + '  ♡  ' + nameB, width / 2, 210);

      // Divider line
      ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(width / 2 - 180, 245);
      ctx.lineTo(width / 2 + 180, 245);
      ctx.stroke();

      // Quote icon
      ctx.fillStyle = 'rgba(255, 75, 114, 0.25)';
      ctx.font = 'bold 160px serif';
      ctx.fillText('“', width / 2, 380);

      // Text wrapping logic
      ctx.fillStyle = '#f8fafc';
      ctx.font = '500 40px sans-serif';
      var maxWidth = 860;
      var lineHeight = 60;
      var words = text.split(' ');
      var line = '';
      var lines = [];

      for (var n = 0; n < words.length; n++) {
        var testLine = line + words[n] + ' ';
        var metrics = ctx.measureText(testLine);
        if (metrics.width > maxWidth && n > 0) {
          lines.push(line);
          line = words[n] + ' ';
        } else {
          line = testLine;
        }
      }
      lines.push(line);

      var startY = 440;
      for (var k = 0; k < lines.length; k++) {
        ctx.fillText(lines[k].trim(), width / 2, startY + (k * lineHeight));
      }

      // Footer branding
      ctx.fillStyle = 'rgba(255, 255, 255, 0.5)';
      ctx.font = '26px sans-serif';
      var brandText = settings.backlinkText || 'Relationship Headcanon Generator';
      ctx.fillText('✨ ' + brandText, width / 2, height - 90);

      // Trigger Download
      try {
        var link = document.createElement('a');
        link.download = (nameA + '-' + nameB + '-headcanon').toLowerCase().replace(/\s+/g, '-') + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        showToast('Image card downloaded!');
      } catch (e) {
        showToast('Unable to export image card.');
      }
    }

    // Attach generate trigger
    if (generateBtn) {
      generateBtn.addEventListener('click', generateHeadcanons);
    }

    // Enter key triggers generate
    [personAInput, personBInput].forEach(function (inp) {
      if (inp) {
        inp.addEventListener('keydown', function (e) {
          if (e.key === 'Enter') {
            generateHeadcanons();
          }
        });
      }
    });

    // Favorites Modal Handlers
    if (favToggleBtn && favModal) {
      favToggleBtn.addEventListener('click', function () {
        renderFavoritesList();
        favModal.style.display = 'flex';
      });

      favModal.querySelectorAll('.rhg-modal-close, .rhg-modal-overlay').forEach(function (el) {
        el.addEventListener('click', function () {
          favModal.style.display = 'none';
        });
      });

      if (clearFavsBtn) {
        clearFavsBtn.addEventListener('click', function () {
          if (confirm('Clear all saved favorites?')) {
            saveFavorites([]);
            renderFavoritesList();
            showToast('Favorites cleared.');
          }
        });
      }
    }

    function renderFavoritesList() {
      if (!favList) return;
      var favs = getFavorites();
      if (!favs.length) {
        favList.innerHTML = '<p style="text-align:center; color:#94a3b8; padding: 20px 0;">No saved headcanons yet.</p>';
        return;
      }
      var html = '';
      favs.forEach(function (item, index) {
        html +=
          '<div style="border-bottom: 1px solid var(--rhg-border); padding: 12px 0;">' +
            '<div style="font-size: 11px; font-weight:700; color:var(--rhg-primary); margin-bottom: 4px;">' + (item.names || 'OTP') + ' • ' + (item.categoryName || '') + '</div>' +
            '<p style="margin: 0 0 8px 0; font-size: 14px; line-height: 1.5;">' + escapeHtml(item.rawText) + '</p>' +
            '<div style="display:flex; gap: 8px;">' +
              '<button type="button" class="rhg-action-btn rhg-fav-item-copy" data-idx="' + index + '">📋 Copy</button>' +
              '<button type="button" class="rhg-action-btn rhg-fav-item-del" data-idx="' + index + '" style="color:#ef4444;">Delete</button>' +
            '</div>' +
          '</div>';
      });
      favList.innerHTML = html;

      // Event delegation for favorites modal actions
      favList.querySelectorAll('.rhg-fav-item-copy').forEach(function (b) {
        b.addEventListener('click', function () {
          var idx = parseInt(this.getAttribute('data-idx'), 10);
          var itm = getFavorites()[idx];
          if (itm && navigator.clipboard) {
            navigator.clipboard.writeText(itm.rawText).then(function () {
              showToast('Copied!');
            });
          }
        });
      });

      favList.querySelectorAll('.rhg-fav-item-del').forEach(function (b) {
        b.addEventListener('click', function () {
          var idx = parseInt(this.getAttribute('data-idx'), 10);
          var favs = getFavorites();
          favs.splice(idx, 1);
          saveFavorites(favs);
          renderFavoritesList();
        });
      });
    }

    // Embed Modal Handlers
    if (embedToggleBtn && embedModal) {
      embedToggleBtn.addEventListener('click', function () {
        embedModal.style.display = 'flex';
      });

      embedModal.querySelectorAll('.rhg-modal-close, .rhg-modal-overlay').forEach(function (el) {
        el.addEventListener('click', function () {
          embedModal.style.display = 'none';
        });
      });

      if (copyEmbedBtn) {
        copyEmbedBtn.addEventListener('click', function () {
          var textarea = embedModal.querySelector('.rhg-embed-code');
          if (textarea) {
            textarea.select();
            document.execCommand('copy');
            showToast('Embed code copied! Paste on your site.');
          }
        });
      }
    }
  }

  // Initialize on all widgets on the page
  document.addEventListener('DOMContentLoaded', function () {
    var widgets = document.querySelectorAll('.rhg-wrapper');
    widgets.forEach(function (widget) {
      initRHG(widget);
    });
  });

})();
