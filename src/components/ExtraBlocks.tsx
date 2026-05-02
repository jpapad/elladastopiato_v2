'use client'
import React from 'react'
import Image from 'next/image'
import Link from 'next/link'
import { motion } from 'framer-motion'
import { Heart, Globe, Award, ArrowRight } from 'lucide-react'

const IconMap: any = { Heart, Globe, Award }

// --- Video Block ---
export const VideoBlock = ({ videoType, youtubeID, videoFile, title }: any) => (
  <section className="max-w-7xl mx-auto px-6 py-20 no-print">
    {title && <h2 className="text-3xl font-black uppercase italic mb-8 tracking-tighter">{title}</h2>}
    <div className="aspect-video w-full rounded-[3rem] overflow-hidden border border-white/10 bg-black shadow-2xl">
      {videoType === 'youtube' ? (
        <iframe 
          className="w-full h-full"
          src={`https://www.youtube.com/embed/${youtubeID}`}
          title={title || "YouTube video"}
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
        />
      ) : (
        <video src={videoFile?.url} controls className="w-full h-full object-cover" />
      )}
    </div>
  </section>
)

// --- Features Block (Τα Κουτάκια) ---
export const FeaturesBlock = ({ tagline, title, features }: any) => (
  <section className="max-w-7xl mx-auto px-6 py-32 text-center no-print">
    {tagline && (
      <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em] block mb-4">
        {tagline}
      </span>
    )}
    <h2 className="text-4xl md:text-6xl font-black uppercase italic tracking-tighter mb-20">{title}</h2>
    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
      {features?.map((f: any, i: number) => {
        const Icon = IconMap[f.icon] || Award
        return (
          <div key={i} className="bg-white/[0.03] border border-white/5 p-10 rounded-[2.5rem] hover:bg-white/[0.05] transition-all group">
            <div className="w-12 h-12 bg-orange-500/10 rounded-2xl flex items-center justify-center text-orange-500 mb-8 group-hover:bg-orange-500 group-hover:text-black transition-colors">
              <Icon size={24} />
            </div>
            <h3 className="text-xl font-black uppercase italic mb-4 tracking-tight">{f.featureTitle}</h3>
            <p className="text-white/40 text-sm leading-relaxed font-light italic">{f.featureDescription}</p>
          </div>
        )
      })}
    </div>
  </section>
)

// --- CTA Block (Πορτοκαλί Button) ---
export const CtaBlock = ({ title, buttonText, link }: any) => (
  <section className="max-w-7xl mx-auto px-6 py-20 no-print">
    <div className="bg-orange-500 rounded-[3rem] p-12 md:p-20 flex flex-col md:flex-row items-center justify-between gap-10">
      <h2 className="text-4xl md:text-6xl font-black uppercase italic tracking-tighter text-black leading-none">
        {title}
      </h2>
      <Link href={link} className="bg-black text-white px-10 py-6 rounded-full font-black uppercase text-xs tracking-[0.2em] flex items-center gap-4 hover:scale-105 transition-transform">
        {buttonText} <ArrowRight size={18} />
      </Link>
    </div>
  </section>
)

// --- Categories Block ---
export const CategoriesBlock = ({ title, items }: any) => (
  <section className="max-w-7xl mx-auto px-6 py-24 no-print">
    <h2 className="text-3xl font-black uppercase italic mb-12 tracking-tighter">{title}</h2>
    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
      {items?.map((item: any, i: number) => (
        <Link key={i} href={item.link} className="group relative h-64 rounded-3xl overflow-hidden border border-white/5">
          <Image src={item.image.url} alt={item.label} fill className="object-cover transition-transform duration-700 group-hover:scale-110 grayscale group-hover:grayscale-0" />
          <div className="absolute inset-0 bg-black/40 flex items-center justify-center">
            <span className="text-sm font-black uppercase tracking-widest">{item.label}</span>
          </div>
        </Link>
      ))}
    </div>
  </section>
)

// --- Story Block ---
export const StoryBlock = ({ title, content, image, imageSide }: any) => (
  <section className="max-w-7xl mx-auto px-6 py-32">
    <div className={`flex flex-col ${imageSide === 'left' ? 'md:flex-row-reverse' : 'md:flex-row'} gap-20 items-center`}>
      <div className="flex-1">
        <h2 className="text-5xl font-black uppercase italic tracking-tighter mb-8 leading-tight">{title}</h2>
        <p className="text-white/40 text-lg leading-loose font-light">{content}</p>
      </div>
      <div className="flex-1 w-full aspect-square relative rounded-[4rem] overflow-hidden border border-white/10 no-print">
        {image?.url && <Image src={image.url} alt={title} fill className="object-cover" />}
      </div>
    </div>
  </section>
)

// --- Quote Block ---
export const QuoteBlock = ({ quote, author }: any) => (
  <section className="py-40 text-center px-6">
    <motion.div initial={{ opacity: 0 }} whileInView={{ opacity: 1 }} viewport={{ once: true }}>
      <p className="text-4xl md:text-6xl font-black italic uppercase tracking-tighter max-w-4xl mx-auto leading-tight mb-8">
        "{quote}"
      </p>
      {author && <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.4em]">— {author}</span>}
    </motion.div>
  </section>
)