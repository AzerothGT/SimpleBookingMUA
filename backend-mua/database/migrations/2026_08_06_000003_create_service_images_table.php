<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('service_id')->index();
            $table->string('image_url');
            $table->string('image_source')->default('upload'); // upload|external
            $table->integer('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER service_images_one_cover_insert
                BEFORE INSERT ON service_images
                FOR EACH ROW
                BEGIN
                    DECLARE locked_service_id CHAR(36);
                    IF NEW.is_cover = 1 THEN
                        SELECT id INTO locked_service_id FROM services WHERE id = NEW.service_id FOR UPDATE;
                        IF EXISTS (SELECT 1 FROM service_images WHERE service_id = NEW.service_id AND is_cover = 1) THEN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Service already has a cover image';
                        END IF;
                    END IF;
                END
                SQL);

            DB::unprepared(<<<'SQL'
                CREATE TRIGGER service_images_one_cover_update
                BEFORE UPDATE ON service_images
                FOR EACH ROW
                BEGIN
                    DECLARE locked_service_id CHAR(36);
                    IF NEW.is_cover = 1 THEN
                        SELECT id INTO locked_service_id FROM services WHERE id = NEW.service_id FOR UPDATE;
                        IF EXISTS (SELECT 1 FROM service_images WHERE service_id = NEW.service_id AND is_cover = 1 AND id <> OLD.id) THEN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Service already has a cover image';
                        END IF;
                    END IF;
                END
                SQL);
        } else {
            DB::statement('CREATE UNIQUE INDEX service_images_one_cover_per_service ON service_images (service_id) WHERE is_cover = 1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_images');
    }
};
