export const CATEGORY_OPTIONS = [
  { label: 'Ορεκτικά',     value: 'orektika' },
  { label: 'Κυρίως Πιάτα', value: 'kyrios' },
  { label: 'Θαλασσινά',    value: 'thalassina' },
  { label: 'Γλυκά',        value: 'glyka' },
  { label: 'Πίτες',        value: 'pites' },
  { label: 'Σαλάτες',      value: 'salates' },
]

const MAP = Object.fromEntries(CATEGORY_OPTIONS.map(c => [c.value, c.label]))

export function getCategoryLabel(value: string): string {
  return MAP[value] ?? value
}
