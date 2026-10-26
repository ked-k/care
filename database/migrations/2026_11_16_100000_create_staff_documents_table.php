<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Batch 39 (Staff Profile & Document Management) — the client's own
 * written request: a digital personnel file per employee covering
 * recruitment documents, right to work, DBS checks, references,
 * qualifications/training, employment documents (contracts etc.), and
 * "additional records" (disciplinary/performance). One table covers all
 * eight categories rather than eight separate tables — they share the same
 * shape (a file, a category, optional issue/expiry dates, a review state)
 * and a single table is what makes one "staff compliance checklist" query
 * possible at all.
 *
 * Follows this app's established convention for an optional file on a
 * record (see training_progress.certificate_media_id, Batch 36): media_id
 * is a plain uuid column, not a DB foreign key — the MediaFile row is
 * created first, then its own related_type/related_id columns are pointed
 * back at this row.
 *
 * status/reviewed_by/reviewed_at/notes exist because the client explicitly
 * asked for staff to be able to upload their own documents "for management
 * approval" — a manager-uploaded document is auto-approved; a self-uploaded
 * one starts pending until a manager reviews it.
 *
 * retention_until is deliberately just a date a manager can optionally set,
 * with nothing in this batch that reads or acts on it — specific UK GDPR
 * retention periods per document category need Affinity's own compliance
 * policy input, not a guess baked into code. See CHANGES39.md.
 *
 * last_reminded_at is the same "don't re-notify on every page load" dedup
 * column training_progress already uses for certificate renewal reminders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('agency_id', 36);
            $table->unsignedBigInteger('user_id'); // the staff member this document belongs to

            $table->enum('category', [
                'recruitment',
                'right_to_work',
                'dbs',
                'references',
                'qualifications_training',
                'employment_documents',
                'additional_records',
            ]);
            $table->string('title'); // e.g. "DBS Certificate", "Signed contract", "CV"

            $table->uuid('media_id')->nullable(); // see class doc comment — plain column, no FK

            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');

            $table->unsignedBigInteger('uploaded_by'); // who actually uploaded it — the staff member themselves, or a manager
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable(); // e.g. a rejection reason

            $table->date('retention_until')->nullable(); // set by a manager; nothing in this batch enforces it yet
            $table->timestamp('last_reminded_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('agency_id')->references('id')->on('agencies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('updated_by')->references('id')->on('users')->restrictOnDelete()->cascadeOnUpdate();

            $table->index(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
    }
};
