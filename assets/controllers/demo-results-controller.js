import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = { url: String };

  search(event) {
    const url = new URL(this.urlValue, window.location.origin);
    url.searchParams.set("q", event.detail.query);
    this.element.src = url.href;
  }
}
