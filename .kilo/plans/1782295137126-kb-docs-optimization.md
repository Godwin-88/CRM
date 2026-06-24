# KB Docs Optimization & Rendering Plan

## Goal
1. Transform all ingested `docs/*.md` content into user-friendly, properly formatted Markdown (no raw spec references like `4.1.4`)
2. Fix the docs section frontend to render Markdown correctly (lists, bold, italics, headings display as intended)
3. Provide a repeatable way to re-optimize all existing articles

## Constraints
- Use existing Groq API key (no new credentials needed, `GROQ_API_KEY` present in `.env`)
- All data access stays within Laravel (no direct DB calls from ml-agents)
- Reuse existing `docs:ingest` command flow where possible
- Do not break existing article metadata (slug, category, feature_refs, permissions)

## Steps
1. **Update `IngestDocs` command for content optimization**
   - Add `--optimize` flag to `docs:ingest`
   - Add helper to call Groq LLM via Laravel HTTP client (use env `GROQ_API_KEY`, model `llama3-70b-8192` or env-configured)
   - LLM prompt requirements:
     - Rewrite raw spec text into user-friendly KB content for agents/managers
     - Remove all spec section references (e.g. `4.1.4`, `Section 4.1`)
     - Use Markdown formatting: headings, bullet lists, bold for key actions/terms, italic for notes/warnings
     - Rephrase acceptance criteria from spec language to clear, actionable user guidance
     - Keep all functional details (permissions, behavior, steps) intact
   - Add regex fallback to strip any leftover spec references after LLM response
   - If Groq call fails, log warning and save unoptimized content (do not block ingestion)
   - If `GROQ_API_KEY` is missing, skip optimization and warn user
   - Add small delay between LLM calls to avoid rate limits

2. **Fix frontend docs Markdown rendering**
   - Locate the docs/article view frontend component
   - Add Markdown parsing library if not already present (e.g. `marked` for JS, or Laravel-side `Parsedown` if rendering server-side)
   - Render article `body` as parsed Markdown instead of plain text
   - Add basic styling for rendered Markdown (list spacing, heading typography, bold/italic support)

3. **Re-ingest and reindex all content**
   - Run `php artisan docs:ingest --force --optimize` to reprocess all docs with optimized content
   - Run `php artisan scout:import "App\Models\KnowledgeBaseArticle"` to update Meilisearch index

4. **Validation**
   - Spot check 3-5 sample articles (e.g. Contact Timeline, Bulk Import, Loyalty Tiers) to confirm:
     - No spec references remain
     - Formatting (lists, bold, headings) renders correctly in the docs section
     - Content is user-friendly and actionable
   - Verify Meilisearch search returns optimized content snippets

## Out of Scope
- Changing article metadata structure (slug, category, feature_refs)
- Adding new KB features beyond rendering and content optimization
- Optimizing non-docs sources of KB content

## Risks & Mitigations
- Groq rate limits: add 1s delay between LLM calls
- Inconsistent LLM output: enforce strict prompt rules + regex fallback for spec references
- Missing markdown library in frontend: add lightweight, well-maintained dependency if needed
