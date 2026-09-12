import ProductController from "./demo-product-controller.js";

/* stimulusFetch: 'lazy' */
export default class extends ProductController {
  static outlets = ["demo-cart"];

  async remove() {
    super.remove();
    await this.demoCartOutlet.remove(this.idValue);
  }
}
