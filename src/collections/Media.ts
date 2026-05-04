import type { CollectionConfig } from 'payload'
import { S3Client, DeleteObjectCommand, PutObjectCommand } from '@aws-sdk/client-s3'

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

export const Media: CollectionConfig = {
  slug: 'media',
  access: { read: () => true },
  upload: {
    disableLocalStorage: true,
  },
  hooks: {
    afterChange: [
      async ({ doc, req, operation }) => {
        if (!process.env.SUPABASE_S3_ENDPOINT) return doc
        if (operation !== 'create' && operation !== 'update') return doc
        try {
          const file = req.file as any
          const buffer: Buffer | undefined = file?.data

          if (buffer && doc.filename) {
            await getS3().send(
              new PutObjectCommand({
                Bucket: BUCKET,
                Key: doc.filename,
                Body: buffer,
                ContentType: doc.mimeType || 'application/octet-stream',
              }),
            )
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
          return { ...doc, url: `${PUBLIC_BASE}/${doc.filename}` }
        }
        return doc
      },
    ],
    afterDelete: [
      async ({ doc, req }) => {
        if (!process.env.SUPABASE_S3_ENDPOINT) return doc
        try {
          if (doc?.filename) {
            await getS3().send(new DeleteObjectCommand({ Bucket: BUCKET, Key: doc.filename }))
          }
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
