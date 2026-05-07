'use client'
import React, { useState, useEffect, useCallback, useRef } from 'react'
import { X, ChevronLeft, ChevronRight, CheckCircle2, Circle, Timer, Flame, Plus, Minus, AlarmClock } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'

function extractSteps(data: any): string[] {
  if (!data) return []
  const steps: string[] = []
  function getText(node: any): string {
    if (node.type === 'text') return node.text || ''
    if (Array.isArray(node.children)) return node.children.map(getText).join('')
    return ''
  }
  function walk(node: any) {
    if (node.type === 'listitem') {
      const t = getText(node).trim()
      if (t) steps.push(t)
      return
    }
    if (Array.isArray(node.children)) node.children.forEach(walk)
  }
  walk(data)
  return steps
}

interface Props {
  instructions: any
  title: string
  totalTime?: number
  onClose: () => void
}

export default function CookingMode({ instructions, title, totalTime, onClose }: Props) {
  const steps = extractSteps(instructions)
  const [current, setCurrent] = useState(0)
  const [done, setDone] = useState<Set<number>>(new Set())
  const [elapsed, setElapsed] = useState(0)

  // Countdown timer
  const [countdown, setCountdown] = useState(0)       // seconds remaining
  const [countInput, setCountInput] = useState(5)     // minutes to set
  const [countRunning, setCountRunning] = useState(false)
  const [countFinished, setCountFinished] = useState(false)
  const alarmRef = useRef<ReturnType<typeof setInterval> | null>(null)

  // Elapsed stopwatch
  useEffect(() => {
    const id = setInterval(() => setElapsed(e => e + 1), 1000)
    return () => clearInterval(id)
  }, [])

  // Countdown tick
  useEffect(() => {
    if (!countRunning) return
    if (countdown <= 0) {
      setCountRunning(false)
      setCountFinished(true)
      // Browser notification
      if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('⏰ Ελλάδα στο Πιάτο', { body: 'Ο χρονόμετρος τελείωσε!', icon: '/og-image.jpg' })
      }
      return
    }
    const id = setInterval(() => setCountdown(c => c - 1), 1000)
    return () => clearInterval(id)
  }, [countRunning, countdown])

  const startTimer = () => {
    if (countInput <= 0) return
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission()
    }
    setCountdown(countInput * 60)
    setCountRunning(true)
    setCountFinished(false)
  }
  const stopTimer = () => { setCountRunning(false); setCountdown(0); setCountFinished(false) }

  const fmt = (s: number) => `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`

  const goTo = useCallback((i: number) => {
    if (i >= 0 && i < steps.length) setCurrent(i)
  }, [steps.length])

  const toggleDone = useCallback((i: number) => {
    setDone(prev => {
      const next = new Set(prev)
      next.has(i) ? next.delete(i) : next.add(i)
      return next
    })
  }, [])

  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if (e.key === 'ArrowRight' || e.key === 'ArrowDown') goTo(current + 1)
      if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') goTo(current - 1)
      if (e.key === 'Escape') onClose()
      if (e.key === ' ') { e.preventDefault(); toggleDone(current) }
    }
    window.addEventListener('keydown', handler)
    return () => window.removeEventListener('keydown', handler)
  }, [current, goTo, toggleDone, onClose])

  if (steps.length === 0) return null

  const progress = ((current + 1) / steps.length) * 100
  const countPct = countdown > 0 && countInput > 0 ? ((countInput * 60 - countdown) / (countInput * 60)) * 100 : 0

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 z-[200] bg-[#0E0C0A] flex flex-col"
    >
      {/* Top bar */}
      <div className="flex items-center justify-between px-8 py-5 border-b border-white/5">
        <div className="flex items-center gap-4">
          <Flame size={20} className="text-orange-500" />
          <span className="text-white/60 text-xs font-black uppercase tracking-widest truncate max-w-xs">{title}</span>
        </div>
        <div className="flex items-center gap-6">
          {/* Elapsed */}
          <div className="flex items-center gap-2 text-white/40 text-xs font-black uppercase tracking-widest">
            <Timer size={13} className="text-orange-500" /> {fmt(elapsed)}
          </div>
          {totalTime && <span className="text-white/20 text-xs font-black uppercase tracking-widest hidden md:block">~{totalTime}'</span>}
          <button onClick={onClose} className="w-9 h-9 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
            <X size={16} className="text-white/60" />
          </button>
        </div>
      </div>

      {/* Overall progress bar */}
      <div className="h-[2px] bg-white/5">
        <motion.div className="h-full bg-orange-500" animate={{ width: `${progress}%` }} transition={{ duration: 0.3 }} />
      </div>

      {/* Body */}
      <div className="flex-1 flex flex-col lg:flex-row overflow-hidden">
        {/* Steps area */}
        <div className="flex-1 flex flex-col items-center justify-center px-6 py-10">
          <div className="w-full max-w-2xl">
            <div className="flex items-center gap-3 mb-8">
              <span className="text-orange-500 text-[10px] font-black uppercase tracking-[0.5em]">
                Βήμα {current + 1} από {steps.length}
              </span>
              {done.has(current) && (
                <span className="flex items-center gap-1 text-green-400 text-[10px] font-black uppercase tracking-widest">
                  <CheckCircle2 size={12} /> Ολοκληρώθηκε
                </span>
              )}
            </div>

            <AnimatePresence mode="wait">
              <motion.p
                key={current}
                initial={{ opacity: 0, x: 20 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -20 }}
                transition={{ duration: 0.25 }}
                className={`text-3xl md:text-5xl font-bold leading-tight mb-12 ${done.has(current) ? 'text-white/30 line-through' : 'text-white'}`}
              >
                {steps[current]}
              </motion.p>
            </AnimatePresence>

            <div className="flex flex-wrap items-center gap-3">
              <button onClick={() => goTo(current - 1)} disabled={current === 0}
                className="flex items-center gap-2 px-5 py-3 rounded-full bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest text-white/60 hover:bg-white/10 disabled:opacity-20 disabled:cursor-not-allowed transition-all">
                <ChevronLeft size={14} /> Προηγούμενο
              </button>
              <button onClick={() => toggleDone(current)}
                className={`flex items-center gap-2 px-5 py-3 rounded-full text-[10px] font-black uppercase tracking-widest transition-all ${done.has(current) ? 'bg-green-500/20 border border-green-500/30 text-green-400' : 'bg-white/5 border border-white/10 text-white/60 hover:bg-white/10'}`}>
                {done.has(current) ? <CheckCircle2 size={14} /> : <Circle size={14} />}
                {done.has(current) ? 'Έγινε' : 'Σήμανε ως Έγινε'}
              </button>
              {current < steps.length - 1 ? (
                <button onClick={() => { toggleDone(current); goTo(current + 1) }}
                  className="flex items-center gap-2 px-5 py-3 rounded-full bg-orange-500 text-black text-[10px] font-black uppercase tracking-widest hover:bg-orange-400 transition-all">
                  Επόμενο <ChevronRight size={14} />
                </button>
              ) : (
                <button onClick={onClose}
                  className="flex items-center gap-2 px-5 py-3 rounded-full bg-green-500 text-black text-[10px] font-black uppercase tracking-widest hover:bg-green-400 transition-all">
                  Τέλος! <CheckCircle2 size={14} />
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Timer panel */}
        <div className="lg:w-72 border-t lg:border-t-0 lg:border-l border-white/5 p-8 flex flex-col gap-6">
          <div className="flex items-center gap-2 text-white/40 text-[10px] font-black uppercase tracking-widest">
            <AlarmClock size={14} className="text-orange-500" /> Χρονόμετρο
          </div>

          {/* Countdown display */}
          <div className="relative flex items-center justify-center">
            <svg className="w-32 h-32 -rotate-90" viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.05)" strokeWidth="6" />
              <circle cx="60" cy="60" r="50" fill="none" stroke={countFinished ? '#22c55e' : '#E8700A'}
                strokeWidth="6" strokeLinecap="round"
                strokeDasharray={`${2 * Math.PI * 50}`}
                strokeDashoffset={`${2 * Math.PI * 50 * (1 - countPct / 100)}`}
                style={{ transition: 'stroke-dashoffset 1s linear' }}
              />
            </svg>
            <div className="absolute text-center">
              <p className={`text-2xl font-black tabular-nums ${countFinished ? 'text-green-400 animate-pulse' : countRunning ? 'text-white' : 'text-white/40'}`}>
                {countRunning || countFinished ? fmt(countdown) : `${countInput}'`}
              </p>
              {countFinished && <p className="text-[9px] font-black uppercase tracking-widest text-green-400">Τέλος!</p>}
            </div>
          </div>

          {/* Set minutes */}
          {!countRunning && !countFinished && (
            <div className="flex items-center justify-center gap-3">
              <button onClick={() => setCountInput(m => Math.max(1, m - 1))}
                className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                <Minus size={12} className="text-white/60" />
              </button>
              <span className="text-white font-black text-lg w-10 text-center">{countInput}'</span>
              <button onClick={() => setCountInput(m => Math.min(99, m + 1))}
                className="w-8 h-8 rounded-full bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                <Plus size={12} className="text-white/60" />
              </button>
            </div>
          )}

          {/* Quick presets */}
          {!countRunning && !countFinished && (
            <div className="flex flex-wrap gap-2 justify-center">
              {[5, 10, 15, 20, 30].map(m => (
                <button key={m} onClick={() => setCountInput(m)}
                  className={`px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest transition-all ${countInput === m ? 'bg-orange-500 text-black' : 'bg-white/5 text-white/40 hover:bg-white/10'}`}>
                  {m}'
                </button>
              ))}
            </div>
          )}

          {/* Start / Stop */}
          <button
            onClick={countRunning || countFinished ? stopTimer : startTimer}
            className={`w-full py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all ${
              countRunning ? 'bg-red-500/20 border border-red-500/30 text-red-400 hover:bg-red-500/30'
              : countFinished ? 'bg-green-500/20 border border-green-500/30 text-green-400 hover:bg-green-500/30'
              : 'bg-orange-500 text-black hover:bg-orange-400'
            }`}
          >
            {countRunning ? 'Διακοπή' : countFinished ? 'Επαναφορά' : 'Εκκίνηση'}
          </button>
        </div>
      </div>

      {/* Steps mini nav */}
      <div className="border-t border-white/5 px-8 py-4 overflow-x-auto">
        <div className="flex gap-2 w-max">
          {steps.map((_, i) => (
            <button key={i} onClick={() => goTo(i)}
              className={`flex-shrink-0 w-8 h-8 rounded-full text-[10px] font-black transition-all ${
                i === current ? 'bg-orange-500 text-black'
                : done.has(i) ? 'bg-green-500/20 text-green-400 border border-green-500/30'
                : 'bg-white/5 text-white/30 hover:bg-white/10'
              }`}>
              {i + 1}
            </button>
          ))}
        </div>
      </div>

      {/* Keyboard hints */}
      <div className="px-8 py-3 flex items-center gap-6 border-t border-white/5">
        {[['←→', 'Πλοήγηση'], ['Space', 'Σήμανση'], ['Esc', 'Έξοδος']].map(([k, l]) => (
          <span key={k} className="text-white/20 text-[9px] font-bold uppercase tracking-widest">
            <kbd className="bg-white/5 px-2 py-0.5 rounded text-white/30 mr-1">{k}</kbd>{l}
          </span>
        ))}
      </div>
    </motion.div>
  )
}
