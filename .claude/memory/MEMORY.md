# Project Memory

## Key Patterns
- Livewire components use anonymous class syntax in blade files (⚡ prefix files)
- Public pages (faq, welcome) are standalone with their own header/footer
- Auth pages use `x-layouts::app.sidebar` layout
- SQLite database - nullable columns needed before backfilling data in migrations
- Pre-existing Auth test failures (419 CSRF, notification issues) - not related to app code changes
- `Str::markdown()` available via Laravel's bundled league/commonmark - no package install needed

## Route Model Binding + Livewire
- When using `getRouteKeyName()` on a model (e.g., returning 'slug'), Livewire's implicit model binding in component methods also uses it
- If admin Livewire components pass integer IDs (e.g., `wire:click="edit({{ $model->id }})"`) and the model uses slug binding, change method signatures from `Model $model` to `int $id` and use `findOrFail()` internally
- Route cache (`php artisan route:clear`) can hide newly added routes

## Flux UI
- Uses `flux:` prefix components (button, input, textarea, heading, etc.)
- `flux:description` for field helper text
- `flux:icon.name` or `flux:icon name="name"` for icons
