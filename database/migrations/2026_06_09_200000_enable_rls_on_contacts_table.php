<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::statement('ALTER TABLE contacts ENABLE ROW LEVEL SECURITY');
        
        DB::statement('CREATE POLICY tenant_isolation_policy ON contacts
                       USING (account_id::text = current_setting(\'app.current_tenant_id\'))');
    }

    public function down(): void {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation_policy ON contacts');
        DB::statement('ALTER TABLE contacts DISABLE ROW LEVEL SECURITY');
    }
};
