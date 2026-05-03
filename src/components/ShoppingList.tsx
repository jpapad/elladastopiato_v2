'use client'
import React, { useState, useMemo } from 'react'
import { ShoppingCart, Download, X, Check, Printer } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'

// Extract ingredient lines from Lexical JSON
function extractIngredients(data: any): string[] {
  if (!data) return []
  const lines: string[] = []

  function getText(node: any): string {
    if (node.type === 'text') return node.text || ''
    if (Array.isArray(node.children)) return node.children.map(getText).join('')
    return ''
  }

  function walk(node: any) {
    if (node.type === 'listitem' || node.type === 'paragraph') {
      const t = getText(node).trim()
      if (t) lines.push(t)
      return
    }
    if (Array.isArray(node.children)) node.children.forEach(walk)
  }

  walk(data)
  return lines
}

interface Props {
  ingredients: any
  recipeTitle: string
}

export default function ShoppingList({ ingredients, recipeTitle }: Props) {
  const [open, setOpen] = useState(false)
  const [checked, setChecked] = useState<Set<number>>(new Set())

  const items = useMemo(() => extractIngredients(ingredients), [ingredients])

  const toggle = (i: number) => {
    setChecked(prev => {
      const next = new Set(prev)
      next.has(i) ? next.delete(i) : next.add(i)
      return next
    })
  }

  const exportPDF = async () => {
    const { jsPDF } = await import('jspdf')
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })

    // Header
    doc.setFillColor(249, 115, 22)
    doc.rect(0, 0, 210, 28, 'F')
    doc.setTextColor(0, 0, 0)
    doc.setFontSize(18)
    doc.setFont('helvetica', 'bold')
    doc.text('Lista Agoras', 14, 12)
    doc.setFontSize(10)
    doc.setFont('helvetica', 'normal')
    doc.text(recipeTitle, 14, 21)

    // Date
    doc.setTextColor(80, 80, 80)
    doc.setFontSize(8)
    doc.text(new Date().toLocaleDateString('el-GR'), 180, 21, { align: 'right' })

    // Items
    let y = 40
    doc.setTextColor(30, 30, 30)
    items.forEach((item, i) => {
      if (y > 270) {
        doc.addPage()
        y = 20
      }
      const isDone = checked.has(i)
      // Checkbox
      doc.setDrawColor(200, 200, 200)
      doc.rect(14, y - 3.5, 5, 5)
      if (isDone) {
        doc.setDrawColor(249, 115, 22)
        doc.setLineWidth(0.8)
        doc.line(14.5, y, 17, y + 2)
        doc.line(17, y + 2, 19.5, y - 2)
        doc.setLineWidth(0.2)
      }
      // Text
      doc.setFontSize(11)
      doc.setFont('helvetica', isDone ? 'normal' : 'normal')
      doc.setTextColor(isDone ? 160 : 30, isDone ? 160 : 30, isDone ? 160 : 30)
      doc.text(item, 22, y)
      // Separator line
      if (i < items.length - 1) {
        doc.setDrawColor(230, 230, 230)
        doc.line(14, y + 3, 196, y + 3)
      }
      y += 10
    })

    doc.save(`lista-agoras-${recipeTitle.toLowerCase().replace(/\s+/g, '-')}.pdf`)
  }

  if (items.length === 0) return null

  return (
    <>
      <button
        onClick={() => setOpen(true)}
        className="no-print flex items-center gap-2 bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 px-5 py-2 rounded-full text-[10px] font-black uppercase tracking-widest hover:bg-orange-500 hover:text-black hover:border-orange-500 transition-all"
      >
        <ShoppingCart size={14} /> Λίστα Αγορών
      </button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-[100] bg-black/60 backdrop-blur-lg flex items-center justify-center p-4"
            onClick={e => { if (e.target === e.currentTarget) setOpen(false) }}
          >
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="bg-white dark:bg-[#111] border border-black/10 dark:border-white/10 rounded-[2.5rem] w-full max-w-md max-h-[80vh] flex flex-col shadow-2xl"
            >
              {/* Header */}
              <div className="flex items-center justify-between px-8 py-6 border-b border-black/5 dark:border-white/5">
                <div className="flex items-center gap-3">
                  <ShoppingCart size={18} className="text-orange-500" />
                  <div>
                    <h3 className="font-black uppercase italic tracking-tight">Λίστα Αγορών</h3>
                    <p className="text-[10px] text-black/40 dark:text-white/40 uppercase tracking-widest font-bold truncate max-w-[200px]">
                      {recipeTitle}
                    </p>
                  </div>
                </div>
                <button onClick={() => setOpen(false)} className="w-8 h-8 rounded-full bg-black/5 dark:bg-white/5 flex items-center justify-center hover:bg-black/10 dark:hover:bg-white/10 transition-colors">
                  <X size={14} />
                </button>
              </div>

              {/* Items */}
              <div className="flex-1 overflow-y-auto px-8 py-6 space-y-2">
                {items.map((item, i) => (
                  <button
                    key={i}
                    onClick={() => toggle(i)}
                    className={`w-full flex items-center gap-4 p-4 rounded-2xl text-left transition-all ${
                      checked.has(i)
                        ? 'bg-green-500/10 border border-green-500/20'
                        : 'bg-black/[0.02] dark:bg-white/[0.02] border border-black/5 dark:border-white/5 hover:bg-black/5 dark:hover:bg-white/5'
                    }`}
                  >
                    <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all ${
                      checked.has(i) ? 'bg-green-500 border-green-500' : 'border-black/20 dark:border-white/20'
                    }`}>
                      {checked.has(i) && <Check size={10} className="text-white" strokeWidth={3} />}
                    </div>
                    <span className={`text-sm font-medium transition-all ${
                      checked.has(i) ? 'line-through text-black/30 dark:text-white/30' : ''
                    }`}>
                      {item}
                    </span>
                  </button>
                ))}
              </div>

              {/* Footer */}
              <div className="px-8 py-5 border-t border-black/5 dark:border-white/5 flex items-center gap-3">
                <span className="text-[10px] text-black/30 dark:text-white/30 uppercase tracking-widest font-bold flex-1">
                  {checked.size}/{items.length} αγοράστηκαν
                </span>
                <button
                  onClick={() => window.print()}
                  className="flex items-center gap-2 px-4 py-2 rounded-full bg-black/5 dark:bg-white/5 border border-black/10 dark:border-white/10 text-[10px] font-black uppercase tracking-widest hover:bg-black/10 transition-all"
                >
                  <Printer size={12} /> Print
                </button>
                <button
                  onClick={exportPDF}
                  className="flex items-center gap-2 px-5 py-2 rounded-full bg-orange-500 text-black text-[10px] font-black uppercase tracking-widest hover:bg-orange-400 transition-all"
                >
                  <Download size={12} /> PDF
                </button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  )
}
