import pg from 'pg'

const { Client } = pg

const client = new Client({
  connectionString: process.env.DATABASE_URI,
})

await client.connect()

const sql = `
  CREATE TABLE IF NOT EXISTS "homepage" (
    "id" serial PRIMARY KEY NOT NULL,
    "updated_at" timestamp(3) with time zone DEFAULT now() NOT NULL,
    "created_at" timestamp(3) with time zone DEFAULT now() NOT NULL
  );

  CREATE TABLE IF NOT EXISTS "homepage_blocks_hero" (
    "_order" integer NOT NULL,
    "_parent_id" integer NOT NULL,
    "_path" text NOT NULL,
    "id" varchar PRIMARY KEY NOT NULL,
    "badge" varchar,
    "title_prefix" varchar,
    "title_highlight" varchar,
    "title_suffix" varchar,
    "subtitle" varchar,
    "button_text" varchar,
    "button_link" varchar,
    "background_image_id" integer,
    "block_name" varchar
  );

  CREATE TABLE IF NOT EXISTS "homepage_blocks_text_section" (
    "_order" integer NOT NULL,
    "_parent_id" integer NOT NULL,
    "_path" text NOT NULL,
    "id" varchar PRIMARY KEY NOT NULL,
    "title_prefix" varchar,
    "title_highlight" varchar,
    "content" varchar,
    "block_name" varchar
  );

  CREATE TABLE IF NOT EXISTS "homepage_blocks_recipes_section" (
    "_order" integer NOT NULL,
    "_parent_id" integer NOT NULL,
    "_path" text NOT NULL,
    "id" varchar PRIMARY KEY NOT NULL,
    "title_prefix" varchar,
    "title_highlight" varchar,
    "limit" numeric DEFAULT 3,
    "show_view_all" boolean DEFAULT true,
    "block_name" varchar
  );

  CREATE TABLE IF NOT EXISTS "homepage_blocks_map_section" (
    "_order" integer NOT NULL,
    "_parent_id" integer NOT NULL,
    "_path" text NOT NULL,
    "id" varchar PRIMARY KEY NOT NULL,
    "title_prefix" varchar,
    "title_highlight" varchar,
    "block_name" varchar
  );

  CREATE TABLE IF NOT EXISTS "homepage_blocks_cta_section" (
    "_order" integer NOT NULL,
    "_parent_id" integer NOT NULL,
    "_path" text NOT NULL,
    "id" varchar PRIMARY KEY NOT NULL,
    "title" varchar,
    "subtitle" varchar,
    "button_text" varchar,
    "button_link" varchar,
    "block_name" varchar
  );

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_hero"
      ADD CONSTRAINT "homepage_blocks_hero_background_image_id_media_id_fk"
      FOREIGN KEY ("background_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_hero"
      ADD CONSTRAINT "homepage_blocks_hero_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."homepage"("id") ON DELETE cascade ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_text_section"
      ADD CONSTRAINT "homepage_blocks_text_section_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."homepage"("id") ON DELETE cascade ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_recipes_section"
      ADD CONSTRAINT "homepage_blocks_recipes_section_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."homepage"("id") ON DELETE cascade ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_map_section"
      ADD CONSTRAINT "homepage_blocks_map_section_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."homepage"("id") ON DELETE cascade ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_cta_section"
      ADD CONSTRAINT "homepage_blocks_cta_section_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."homepage"("id") ON DELETE cascade ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  CREATE INDEX IF NOT EXISTS "homepage_blocks_hero_order_idx" ON "homepage_blocks_hero" ("_order");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_hero_parent_id_idx" ON "homepage_blocks_hero" ("_parent_id");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_text_section_order_idx" ON "homepage_blocks_text_section" ("_order");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_text_section_parent_id_idx" ON "homepage_blocks_text_section" ("_parent_id");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_recipes_section_order_idx" ON "homepage_blocks_recipes_section" ("_order");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_recipes_section_parent_id_idx" ON "homepage_blocks_recipes_section" ("_parent_id");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_map_section_order_idx" ON "homepage_blocks_map_section" ("_order");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_map_section_parent_id_idx" ON "homepage_blocks_map_section" ("_parent_id");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_cta_section_order_idx" ON "homepage_blocks_cta_section" ("_order");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_cta_section_parent_id_idx" ON "homepage_blocks_cta_section" ("_parent_id");

  INSERT INTO "homepage" ("id", "updated_at", "created_at")
  VALUES (1, now(), now())
  ON CONFLICT ("id") DO NOTHING;
`

try {
  await client.query(sql)
  console.log('✅ Homepage tables created successfully')
} catch (err) {
  console.error('❌ Error:', err.message)
} finally {
  await client.end()
}
