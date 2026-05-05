import pg from 'pg'
const { Client } = pg

const client = new Client({ connectionString: process.env.DATABASE_URI })
await client.connect()

const sql = `
  -- Hero Slider block table
  CREATE TABLE IF NOT EXISTS "homepage_blocks_hero_slider" (
    "_order" integer NOT NULL,
    "_parent_id" integer NOT NULL,
    "_path" text NOT NULL,
    "id" varchar PRIMARY KEY NOT NULL,
    "autoplay" boolean DEFAULT true,
    "autoplay_interval" varchar DEFAULT '5000',
    "block_name" varchar
  );

  CREATE TABLE IF NOT EXISTS "homepage_blocks_hero_slider_slides" (
    "_order" integer NOT NULL,
    "_parent_id" varchar NOT NULL,
    "id" varchar PRIMARY KEY NOT NULL,
    "badge" varchar,
    "title_prefix" varchar,
    "title_highlight" varchar,
    "title_suffix" varchar,
    "subtitle" varchar,
    "button_text" varchar,
    "button_link" varchar,
    "background_image_id" integer
  );

  -- Travel info columns on locations
  ALTER TABLE "locations" ADD COLUMN IF NOT EXISTS "gallery" jsonb;
  ALTER TABLE "locations" ADD COLUMN IF NOT EXISTS "highlights" varchar;
  ALTER TABLE "locations" ADD COLUMN IF NOT EXISTS "best_time_to_visit" varchar;
  ALTER TABLE "locations" ADD COLUMN IF NOT EXISTS "how_to_get_there" varchar;
  ALTER TABLE "locations" ADD COLUMN IF NOT EXISTS "local_products" varchar;
  ALTER TABLE "locations" ADD COLUMN IF NOT EXISTS "travel_tips" varchar;

  -- Foreign keys
  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_hero_slider"
      ADD CONSTRAINT "homepage_blocks_hero_slider_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."homepage"("id") ON DELETE cascade ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_hero_slider_slides"
      ADD CONSTRAINT "homepage_blocks_hero_slider_slides_background_image_id_fk"
      FOREIGN KEY ("background_image_id") REFERENCES "public"."media"("id") ON DELETE set null ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  DO $$ BEGIN
    ALTER TABLE "homepage_blocks_hero_slider_slides"
      ADD CONSTRAINT "homepage_blocks_hero_slider_slides_parent_id_fk"
      FOREIGN KEY ("_parent_id") REFERENCES "public"."homepage_blocks_hero_slider"("id") ON DELETE cascade ON UPDATE no action;
  EXCEPTION WHEN duplicate_object THEN NULL; END $$;

  -- Indexes
  CREATE INDEX IF NOT EXISTS "homepage_blocks_hero_slider_order_idx" ON "homepage_blocks_hero_slider" ("_order");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_hero_slider_parent_id_idx" ON "homepage_blocks_hero_slider" ("_parent_id");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_hero_slider_slides_order_idx" ON "homepage_blocks_hero_slider_slides" ("_order");
  CREATE INDEX IF NOT EXISTS "homepage_blocks_hero_slider_slides_parent_id_idx" ON "homepage_blocks_hero_slider_slides" ("_parent_id");
`

try {
  await client.query(sql)
  console.log('✅ Hero Slider tables + Location travel fields created successfully')
} catch (err) {
  console.error('❌ Error:', err.message)
} finally {
  await client.end()
}
