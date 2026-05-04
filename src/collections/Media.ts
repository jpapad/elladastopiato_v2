import type { CollectionConfig } from 'payload'
import { S3Client, DeleteObjectCommand, PutObjectCommand } from '@aws-sdk/client-s3'
import fs from 'fs'
import path from 'path'

const BUCKET = process.env.SUPABASE_S3_BUCKET || 'media'
const PROJECT_REF = 'rarfpfqxbeajcsnhawyu'
const PUBLIC_BASE = `https://${PROJECT_REF}.supabase.co/storage/v1/object/public/${BUCKET}`

const getS3 = () =>
  new S3Client({
    region: process.env.SUPABASE_S3_REGION || 'eu-west-1',
    endpoint: process.env.SUPABASE_S3_ENDPOINT,
    credentials: {
      accessKeyId: process.env.SUPABASE_S3_ACCESS_KEY_ID || '',
      secretAccessKey: process.env.SUPABASE_S3_SECRET_ACCESS_KEY || '',
    },
    forcePathStyle: true,
  })

async function uploadToSupabase(filename: string, buffer: Buffer, mimeType: string) {
  if (!process.env.SUPABASE_S3_ENDPOINT) return
  await getS3().send(
    new PutObjectCommand({
      Bucket: BUCKET,
      Key: filename,
      Body: buffer,
      ContentType: mimeType,
    }),
  )
}

async function deleteFromSupabase(filename: string) {
  if (!process.env.SUPABASE_S3_ENDPOINT) return
  await getS3().send(new DeleteObjectCommand({ Bucket: BUCKET, Key: filename }))
}

export const Media: CollectionConfig = {
  slug: 'media',
  access: { read: () => true },
  upload: true,
  hooks: {
    afterChange: [
      async ({ doc, req, operation }) => {
        if (!process.env.SUPABASE_S3_ENDPOINT) return doc
        if (operation !== 'create' && operation !== 'update') return doc
        try {
          let buffer: Buffer | undefined

          // Try req.file first (in-memory)
          if (req.file) {
            const ab = await (req.file as unknown as File).arrayBuffer()
            buffer = Buffer.from(ab)
          } else if (doc.filename) {
            // Fallback: read from local disk (Vercel ephemeral fs)
            const localPath = path.join(process.cwd(), 'media', doc.filename)
            if (fs.existsSync(localPath)) {
              buffer = fs.readFileSync(localPath)
            }
          }

          if (buffer && doc.filename) {
            await uploadToSupabase(doc.filename, buffer, doc.mimeType || 'application/octet-stream')
          }
        } catch (err) {
          req.payload.logger.error({ err }, 'Supabase upload failed')
        }
        return doc
      },
    ],
    afterRead: [
      ({ doc }) => {
        if (!process.env.SUPABASE_S3_ENDPOINT) return doc
        if (doc?.filename) {
          doc.url = `${PUBLIC_BASE}/${doc.filename}`
        }
        return doc
      },
    ],
    afterDelete: [
      async ({ doc, req }) => {
        if (!process.env.SUPABASE_S3_ENDPOINT) return doc
        try {
          if (doc?.filename) await deleteFromSupabase(doc.filename)
        } catch (err) {
          req.payload.logger.error({ err }, 'Supabase delete failed')
        }
        return doc
      },
    ],
  },
  fields: [
    {
      name: 'alt',
      type: 'text',
      required: true,
    },
  ],
}
