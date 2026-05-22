<!-- Main Container -->
<div class="forge-container mt-8 mb-8">
    <header class="animate-fade">
        <h1>Welcome to CodeForge-Engine</h1>
        <p>You are running in <strong>Professional Framework Mode</strong>. This page is served by <code>HomeController</code>, utilizing custom ActiveRecord models (<code>Book</code> and <code>Rental</code>), and wrapped inside a master layout file.</p>
    </header>

    <!-- Database connectivity info -->
    <?php if (isset($dbError)): ?>
        <div class="forge-alert forge-alert-danger mt-4 animate-slide">
            <strong>Database Connection Error:</strong> Check Apache/MySQL settings in XAMPP. Detail: <?php echo htmlspecialchars($dbError); ?>
        </div>
    <?php else: ?>
        <div class="forge-alert forge-alert-success mt-4 animate-slide">
            <strong>Database Online:</strong> Connected successfully! All tables mapped via ActiveRecord <code>Model.php</code> wrapper.
        </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="forge-grid cols-3 mt-4 animate-slide">
        <div class="forge-card">
            <h3>Total Book Copies</h3>
            <p class="stat-number"><?php echo $totalBooks; ?></p>
            <p>Queried using <code>Book::all()</code> model records.</p>
        </div>
        <div class="forge-card">
            <h3>Active Loans</h3>
            <p class="stat-number"><?php echo $activeRentalsCount; ?></p>
            <p>Checked using <code>Rental::query()->where(...)</code> builder.</p>
        </div>
        <div class="forge-card">
            <h3>Active Record</h3>
            <p class="stat-number" style="color: var(--success);">ORM</p>
            <p>Custom mapped database schemas.</p>
        </div>
    </div>

    <!-- Catalog Section -->
    <section class="mt-8 animate-slide">
        <h2>Live Book Catalog from ActiveRecord</h2>
        <div class="forge-table-responsive mt-4">
            <table class="forge-table">
                <thead>
                    <tr>
                        <th>Book ID</th>
                        <th>Book Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Copies Available</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($books)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No books found in database. Run migrations to seed data.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><code>#<?php echo $book->id; ?></code></td>
                                <td><strong><?php echo htmlspecialchars($book->title); ?></strong></td>
                                <td><?php echo htmlspecialchars($book->author); ?></td>
                                <td><?php echo htmlspecialchars($book->category); ?></td>
                                <td><code><?php echo htmlspecialchars($book->isbn); ?></code></td>
                                <td><?php echo "{$book->available_copies} / {$book->total_copies}"; ?></td>
                                <td>
                                    <?php if ($book->available_copies > 0): ?>
                                        <span class="forge-badge forge-badge-success">In Stock</span>
                                    <?php else: ?>
                                        <span class="forge-badge forge-badge-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- UI Components Demo Suite -->
    <section id="ui-components" class="mt-8 animate-slide">
        <h2>Interactive UI Elements (forge-core.js)</h2>
        <p>Testing interactive components built with vanilla JS helpers.</p>

        <div class="forge-grid cols-2 mt-4">
            <!-- Toast and Modal triggers -->
            <div class="forge-card">
                <div class="forge-card-header">
                    <h4 class="forge-card-title">Dialogs & Alerts</h4>
                    <span class="forge-card-subtitle">Micro-animations</span>
                </div>
                <div class="forge-flex">
                    <button class="forge-btn forge-btn-primary" onclick="Forge.toast('Success toast triggered!', 'success')">Dispatch Success Toast</button>
                    <button class="forge-btn forge-btn-danger" onclick="Forge.toast('Danger toast triggered!', 'danger')">Dispatch Danger Toast</button>
                    <button class="forge-btn forge-btn-secondary" onclick="Forge.modal('demo-modal', 'show')">Open Modal</button>
                </div>
            </div>

            <!-- Input group validations -->
            <div class="forge-card">
                <div class="forge-card-header">
                    <h4 class="forge-card-title">Live Form Validation</h4>
                    <span class="forge-card-subtitle">Automatic inputs feedback</span>
                </div>
                <form id="demo-validation-form">
                    <div class="forge-form-group">
                        <label for="val-email" class="forge-label">Email Address (Required)</label>
                        <input type="email" id="val-email" class="forge-input" placeholder="enter email..." required>
                    </div>
                    <button type="submit" class="forge-btn forge-btn-primary w-full" onclick="event.preventDefault(); Forge.validateForm(document.getElementById('demo-validation-form')) ? Forge.toast('Form validated successfully!', 'success') : Forge.toast('Please check required fields.', 'danger');">Validate Form</button>
                </form>
            </div>
        </div>
    </section>
</div>

<!-- Modal Markup -->
<div id="demo-modal" class="forge-modal-backdrop">
    <div class="forge-modal">
        <div class="forge-modal-header">
            <h3 style="margin: 0;">CodeForge Modal</h3>
            <button class="forge-modal-close">&times;</button>
        </div>
        <p>This is a premium-looking custom modal created from scratch. It locks the page scroll and features frosted glass backdrops.</p>
        <div class="mt-4 forge-flex" style="justify-content: flex-end;">
            <button class="forge-btn forge-btn-secondary" onclick="Forge.modal('demo-modal', 'hide')">Cancel</button>
            <button class="forge-btn forge-btn-primary" onclick="Forge.toast('Action confirmed!', 'success'); Forge.modal('demo-modal', 'hide')">Confirm</button>
        </div>
    </div>
</div>
