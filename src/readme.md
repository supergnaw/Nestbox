# Nestbox

_Nestbox_'s purpose is to be a PDO wrapper for easily impelmenting database functionality within a PHP project.

## Nestbox Birds

*(or classes, or packages, or however you want to view them)*

Each bird *(class)* in the "nestbox" serves as a way to add specific functionality to Nestbox.

| Bird                                 | Description                                                                                  |
|--------------------------------------|----------------------------------------------------------------------------------------------|
| [Nestbox](src/readme.md)             | The core "abstract" class that interfaces each component class of Nestbox with the database. |
| [Babbler](src/Babbler/readme.md)     | Content management for website/blog functionality.                                           |
| [Bullfinch](src/Bullfinch/readme.md) | Message board management. *(Not yet complete/available)*                                     |
| [Cuckoo](src/Cuckoo/readme.md)       | Transparent in-line encryption for queries. *(Not yet complete/available)*                   |
| [Lorikeet](src/Lorikeet/readme.md)   | An image upload processing and indexing. *(Not yet complete/available)*                      |
| [Macaw](src/Macaw/readme.md)         | Provides local PlayFab data capable of near real time parity using built-in API throttling.  |
| [Magpie](src/Magpie/readme.md)       | A user and role permissions manager.                                                         |
| [Sternidae](src/Sternidae/readme.md) | Historical and future flight tracking tool. *(Not yet complete/available)*                   |
| [Titmouse](src/Titmouse/readme.md)   | User registration and session management with built-in password best-practicces.             |
| [Weaver](src/Weaver/readme.md)       | REST API endpoint management. *(Not yet complete/available)*                                 |
| [Veery](src/Veery/readme.md)         | A way to collect and store weather forecast data _(Not yet complete/available)_              |

# Development & Contribution

The following is intended to provide guidelines for future development

## Class Structure

For code cleanliness, code consistancy, and future development and maintenance, each class should have a structure
matching the following:

### Class Name

- The name must be a bird for no reason other than thematic reasoning
- The constant `PACKAGE_NAME` must be set to match the bird name of the class

### Class Settings

- classes may use the **nestbox_settings** table to store perpetual configurations
- Settings are automatically loaded and saved based on variable names
- Any variables starting with the class bird name will be loaded from the settings table with `load_settings()` and
  saved to the settings table with `save_settings()`

### Class Tables

- Each table a class uses should have a unique function to create each table and associated triggers/views that is
  required for the class to work
- Each table creation function must start with `create_class_table_`
- Whenever _Nestbox_ encounters an **InvalidTableException**, it attempts to create all class tables, then
  reattempts the query that threw the exception, but this only works if the functions are named appropriately

### Class Methods

- The remaining code of the class will be the class functions organized in a logical flow in order of how they might
  be used in practice within a project
- Function visibility should be practiced where only the functions intended for users are public and all remaining
  will be private.

### Documentation

- Document of each public function is required, but optional for private or protected functions
