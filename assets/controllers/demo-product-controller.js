import { Controller } from "@hotwired/stimulus";

// Keep favorites while Turbo replaces the results, for this page only.
const favorites = new Set();

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ["favorite"];
  static values = { id: Number, favorite: Boolean };

  connect() {
    this.favoriteValue = favorites.has(this.idValue);
  }

  toggleFavorite() {
    this.favoriteValue = !this.favoriteValue;
    if (this.favoriteValue) favorites.add(this.idValue);
    else favorites.delete(this.idValue);
    this.dispatch("change", { detail: { product: this.idValue, favorite: this.favoriteValue } });
  }

  favoriteValueChanged(value) {
    this.favoriteTarget.setAttribute("aria-pressed", String(value));
  }

  remove() {
    // Turbo follows the link and removes this row with a real stream response.
    this.dispatch("removed", { detail: { product: this.idValue } });
  }
}
