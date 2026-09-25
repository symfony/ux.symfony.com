import { Controller } from "@hotwired/stimulus";
import { getComponent } from "@symfony/ux-live-component";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  async remove(product) {
    const cart = await getComponent(this.element);
    await cart.action("remove", { product });
  }
}
