<?php
/**
 * Chat Component: Floating Chat Panel
 *
 * Included in footer.php for all users. Guests see read-only.
 * Expects from parent scope: $chatBeachId, $chatBeachName, $chatIsAuthenticated
 */

$chatIsAuthenticated = $chatIsAuthenticated ?? isAuthenticated();
$chatLang = getCurrentLanguage();
$chatRedirectUrl = urlencode($_SERVER['REQUEST_URI'] ?? '/');
?>
<!-- Chat Floating Bubble + Panel -->
<div class="chat-fab-container"
     id="chat-container"
     data-beach-id="<?= h($chatBeachId ?? '') ?>"
     data-beach-name="<?= h($chatBeachName ?? '') ?>"
     data-authenticated="<?= $chatIsAuthenticated ? '1' : '0' ?>"
     data-csrf="<?= $chatIsAuthenticated ? h(csrfToken()) : '' ?>">

    <!-- Chat Panel -->
    <div id="chat-panel" class="chat-panel closed">

        <!-- Header: Inbox View -->
        <div class="chat-panel-header" id="chat-header-inbox">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white"><?= h(__('chat.chat')) ?></h3>
                    <p class="text-[11px] text-warm-500"><?= h(__('chat.discussions_messages')) ?></p>
                </div>
                <button data-action="closeChatPanel" class="p-1.5 rounded-lg hover:bg-white/10 text-white/50 transition-colors" aria-label="Close chat">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
        </div>

        <!-- Header: Thread View (hidden by default) -->
        <div class="chat-panel-header hidden" id="chat-header-thread">
            <div class="flex items-center gap-2">
                <button data-action="chatBack" class="p-1 rounded-lg hover:bg-white/10 text-white/50 transition-colors" aria-label="Back to inbox">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </button>
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <div id="chat-thread-icon" class="w-7 h-7 rounded-lg bg-cyan-800/50 flex items-center justify-center text-xs shrink-0">💬</div>
                    <div class="min-w-0">
                        <h3 id="chat-thread-title" class="text-sm font-bold text-white truncate"></h3>
                        <p id="chat-thread-subtitle" class="text-[11px] text-warm-500"></p>
                    </div>
                </div>
                <button data-action="closeChatPanel" class="p-1.5 rounded-lg hover:bg-white/10 text-white/50 transition-colors" aria-label="Close chat">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            </div>
        </div>

        <!-- Body: Inbox (loaded via HTMX on first open) -->
        <div id="chat-inbox" class="chat-messages">
            <div class="px-4 py-8 text-center">
                <div class="inline-block w-5 h-5 border-2 border-white/20 border-t-white/60 rounded-full animate-spin"></div>
            </div>
        </div>

        <!-- Body: Thread Messages (hidden by default) -->
        <div id="chat-thread" class="chat-messages hidden px-3 py-3"></div>

        <!-- Compose Bar (auth users in thread view) -->
        <?php if ($chatIsAuthenticated): ?>
        <div id="chat-compose" class="chat-compose hidden">
            <div class="flex gap-2 items-end">
                <textarea id="chat-compose-input"
                          class="flex-1"
                          rows="1"
                          maxlength="500"
                          placeholder="<?= h(__('chat.type_message')) ?>"
                          data-on="keydown"
                          data-action="chatSendOnEnter"
                          data-action-keys="Enter"></textarea>
                <button class="chat-compose-btn" data-action="chatSendMessage">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
        </div>
        <?php else: ?>
        <!-- Guest: Sign-in CTA -->
        <div id="chat-compose" class="chat-signin-cta hidden">
            <a href="<?= h(routeUrl('login', $chatLang)) ?>?redirect=<?= $chatRedirectUrl ?>"><?= h(__('chat.sign_in_to_chat')) ?></a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Floating Action Button -->
    <button id="chat-fab" class="chat-fab" data-action="toggleChatPanel" aria-label="Open chat">
        <svg id="chat-fab-icon-open" class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <svg id="chat-fab-icon-close" class="w-6 h-6 text-white hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
        <!-- Badge (updated via HTMX polling) -->
        <span id="chat-fab-badge-container">
            <?php if ($chatIsAuthenticated): ?>
            <span hx-get="/api/chat/unread"
                  hx-trigger="load, every 30s"
                  hx-target="#chat-fab-badge-container"
                  hx-swap="innerHTML"></span>
            <?php endif; ?>
        </span>
    </button>
</div>

<!-- Chat poll trigger (for thread view, managed by JS) -->
<div id="chat-poll-container"></div>
