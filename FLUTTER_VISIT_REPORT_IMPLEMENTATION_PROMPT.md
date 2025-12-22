# Flutter Implementation Prompt: Visit Report Feature

## Context
I need you to implement a comprehensive Visit Report feature in the Flutter mobile app. This feature allows users to create detailed reports after visiting a client, including multiple photos and various observations.

## Feature Overview
After completing a client visit, users should be able to create a report that includes:
1. **Photo de façade** (Facade photos) - Multiple photos of the store exterior
2. **Photo rayons** (Shelves photos) - Multiple photos of product displays/shelves
3. **Photos supplémentaires** (Additional photos) - Any other relevant photos
4. **Manager presence checkbox** - Was the manager present?
5. **Order status** - Was an order made? Does client need an order?
6. **Stock shortage observations** - Rupture de stock observée
7. **Competitor activity observations** - Activité concurrente observée
8. **Comments section** - General observations

## API Endpoint

### Create/Update Visit Report
**Endpoint**: `POST /api/visits/{visitId}/report`

**Content-Type**: `multipart/form-data`

**Authentication**: Bearer token required

### Request Parameters

#### Required Fields
```dart
{
  "latitude": 33.589886,        // Current GPS latitude
  "longitude": -7.603869        // Current GPS longitude
}
```

#### Optional Boolean Fields
```dart
{
  "manager_present": true,                    // Manager was present
  "order_made": true,                         // Order was placed
  "needs_order": false,                       // Client needs to order
  "stock_shortage_observed": true,            // Stock rupture observed
  "competitor_activity_observed": false       // Competitor activity seen
}
```

#### Optional Text Fields
```dart
{
  "order_reference": "ORD-2025-001",          // Order reference number
  "order_estimated_amount": 15000.50,         // Order amount in XOF
  "stock_issues": "Rupture sur produits A et B",
  "competitor_activity": "Concurrent X a visité hier",
  "comments": "Visite très productive. Client satisfait."
}
```

#### Photo Fields (Arrays of Files)
```dart
{
  "photo_facade": [File, File, ...],          // Facade photos
  "photo_shelves": [File, File, ...],         // Shelves photos
  "photos_other": [File, File, ...]           // Additional photos
}
```

#### Photo GPS (Optional)
Each photo can have its own GPS coordinates:
```dart
{
  "photo_facade_gps[0][latitude]": 33.589886,
  "photo_facade_gps[0][longitude]": -7.603869,
  "photo_shelves_gps[0][latitude]": 33.589886,
  "photo_shelves_gps[0][longitude]": -7.603869,
  // ... etc
}
```

### Success Response (201 Created)
```json
{
  "status": true,
  "message": "Visit report created successfully",
  "uploaded_photos_count": 5,
  "data": {
    "id": 1,
    "visit_id": 123,
    "latitude": 33.589886,
    "longitude": -7.603869,
    "manager_present": true,
    "order_made": true,
    "needs_order": false,
    "order_reference": "ORD-2025-001",
    "order_estimated_amount": 15000.00,
    "stock_issues": "Rupture sur produits A et B",
    "stock_shortage_observed": true,
    "competitor_activity": null,
    "competitor_activity_observed": false,
    "comments": "Visite très productive",
    "validated_at": "2025-12-22T14:30:00Z",
    "is_validated": true,
    "photos": [
      {
        "id": 1,
        "url": "https://api.example.com/storage/visit_report_photos/abc123.jpg",
        "file_name": "facade1.jpg",
        "type": "facade",
        "title": "Photo de façade",
        "latitude": 33.589886,
        "longitude": -7.603869,
        "taken_at": "2025-12-22T14:30:00Z"
      }
      // ... more photos
    ],
    "created_at": "2025-12-22T14:30:00Z",
    "updated_at": "2025-12-22T14:30:00Z"
  }
}
```

## Implementation Requirements

### 1. Data Models

Create Dart models for the visit report:

```dart
class VisitReport {
  final int id;
  final int visitId;
  final double latitude;
  final double longitude;
  final bool managerPresent;
  final bool orderMade;
  final bool needsOrder;
  final String? orderReference;
  final double? orderEstimatedAmount;
  final String? stockIssues;
  final bool stockShortageObserved;
  final String? competitorActivity;
  final bool competitorActivityObserved;
  final String? comments;
  final DateTime? validatedAt;
  final bool isValidated;
  final List<ReportPhoto> photos;
  final DateTime createdAt;
  final DateTime updatedAt;

  VisitReport({
    required this.id,
    required this.visitId,
    required this.latitude,
    required this.longitude,
    required this.managerPresent,
    required this.orderMade,
    required this.needsOrder,
    this.orderReference,
    this.orderEstimatedAmount,
    this.stockIssues,
    required this.stockShortageObserved,
    this.competitorActivity,
    required this.competitorActivityObserved,
    this.comments,
    this.validatedAt,
    required this.isValidated,
    required this.photos,
    required this.createdAt,
    required this.updatedAt,
  });

  factory VisitReport.fromJson(Map<String, dynamic> json) {
    return VisitReport(
      id: json['id'],
      visitId: json['visit_id'],
      latitude: (json['latitude'] as num).toDouble(),
      longitude: (json['longitude'] as num).toDouble(),
      managerPresent: json['manager_present'] ?? false,
      orderMade: json['order_made'] ?? false,
      needsOrder: json['needs_order'] ?? false,
      orderReference: json['order_reference'],
      orderEstimatedAmount: json['order_estimated_amount'] != null
          ? (json['order_estimated_amount'] as num).toDouble()
          : null,
      stockIssues: json['stock_issues'],
      stockShortageObserved: json['stock_shortage_observed'] ?? false,
      competitorActivity: json['competitor_activity'],
      competitorActivityObserved: json['competitor_activity_observed'] ?? false,
      comments: json['comments'],
      validatedAt: json['validated_at'] != null
          ? DateTime.parse(json['validated_at'])
          : null,
      isValidated: json['is_validated'] ?? false,
      photos: (json['photos'] as List<dynamic>?)
              ?.map((photo) => ReportPhoto.fromJson(photo))
              .toList() ??
          [],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}

class ReportPhoto {
  final int id;
  final String url;
  final String fileName;
  final String type; // 'facade', 'shelves', 'other'
  final String title;
  final String? description;
  final double? latitude;
  final double? longitude;
  final DateTime? takenAt;

  ReportPhoto({
    required this.id,
    required this.url,
    required this.fileName,
    required this.type,
    required this.title,
    this.description,
    this.latitude,
    this.longitude,
    this.takenAt,
  });

  factory ReportPhoto.fromJson(Map<String, dynamic> json) {
    return ReportPhoto(
      id: json['id'],
      url: json['url'],
      fileName: json['file_name'],
      type: json['type'],
      title: json['title'],
      description: json['description'],
      latitude: json['latitude'] != null ? (json['latitude'] as num).toDouble() : null,
      longitude: json['longitude'] != null ? (json['longitude'] as num).toDouble() : null,
      takenAt: json['taken_at'] != null ? DateTime.parse(json['taken_at']) : null,
    );
  }
}
```

### 2. UI Design Requirements

#### Screen Structure
Create a `CreateVisitReportScreen` with the following sections:

```
┌─────────────────────────────────────────┐
│  ← Retour    Rapport de Visite    [✓]  │
├─────────────────────────────────────────┤
│                                         │
│  📸 Photos de Façade                    │
│  ┌──────┐ ┌──────┐ ┌──────┐           │
│  │ IMG  │ │ IMG  │ │  +   │           │
│  └──────┘ └──────┘ └──────┘           │
│                                         │
│  📸 Photos Rayons                       │
│  ┌──────┐ ┌──────┐ ┌──────┐           │
│  │ IMG  │ │ IMG  │ │  +   │           │
│  └──────┘ └──────┘ └──────┘           │
│                                         │
│  📸 Photos Supplémentaires              │
│  ┌──────┐ ┌──────┐ ┌──────┐           │
│  │ IMG  │ │ IMG  │ │  +   │           │
│  └──────┘ └──────┘ └──────┘           │
│                                         │
│  ✅ Observations                        │
│  ☐ Manager présent                     │
│  ☐ Commande effectuée                  │
│  ☐ Besoin de commander                 │
│  ☐ Rupture de stock observée           │
│  ☐ Activité concurrente observée       │
│                                         │
│  📝 Détails Commande                    │
│  ┌─────────────────────────────────┐   │
│  │ Référence commande              │   │
│  └─────────────────────────────────┘   │
│  ┌─────────────────────────────────┐   │
│  │ Montant estimé (XOF)            │   │
│  └─────────────────────────────────┘   │
│                                         │
│  📝 Observations Stock                  │
│  ┌─────────────────────────────────┐   │
│  │ Détails rupture de stock        │   │
│  │                                 │   │
│  └─────────────────────────────────┘   │
│                                         │
│  📝 Activité Concurrente                │
│  ┌─────────────────────────────────┐   │
│  │ Détails activité concurrente    │   │
│  │                                 │   │
│  └─────────────────────────────────┘   │
│                                         │
│  📝 Commentaires Généraux               │
│  ┌─────────────────────────────────┐   │
│  │ Vos commentaires sur la visite  │   │
│  │                                 │   │
│  │                                 │   │
│  └─────────────────────────────────┘   │
│                                         │
│  ┌─────────────────────────────────┐   │
│  │   Enregistrer le Rapport        │   │
│  └─────────────────────────────────┘   │
│                                         │
└─────────────────────────────────────────┘
```

#### UI Components Needed

1. **Photo Section Component**
   - Horizontal scrollable list
   - Add photo button (+)
   - Delete photo button (X on each photo)
   - Photo preview thumbnails
   - Camera and gallery options

2. **Checkbox Section**
   - Custom styled checkboxes with French labels
   - Conditional visibility (show order fields only if order_made is checked)

3. **Text Input Sections**
   - TextField for order reference
   - TextField for order amount (numeric keyboard)
   - TextArea for stock issues (multiline)
   - TextArea for competitor activity (multiline)
   - TextArea for general comments (multiline)

4. **Save Button**
   - Fixed at bottom or floating action button
   - Shows loading spinner while uploading
   - Disabled until required fields are filled

### 3. Photo Management

#### Image Picker Implementation
Use `image_picker` package:

```dart
import 'package:image_picker/image_picker.dart';

class PhotoManager {
  final ImagePicker _picker = ImagePicker();

  Future<File?> pickImageFromCamera() async {
    final XFile? image = await _picker.pickImage(
      source: ImageSource.camera,
      maxWidth: 1920,
      maxHeight: 1080,
      imageQuality: 85,
    );

    return image != null ? File(image.path) : null;
  }

  Future<File?> pickImageFromGallery() async {
    final XFile? image = await _picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1920,
      maxHeight: 1080,
      imageQuality: 85,
    );

    return image != null ? File(image.path) : null;
  }

  Future<List<File>> pickMultipleImages() async {
    final List<XFile> images = await _picker.pickMultipleImages(
      maxWidth: 1920,
      maxHeight: 1080,
      imageQuality: 85,
    );

    return images.map((xFile) => File(xFile.path)).toList();
  }
}
```

#### Photo Selection Dialog
```dart
Future<void> showPhotoSourceDialog(BuildContext context, Function(File) onPhotoSelected) async {
  await showModalBottomSheet(
    context: context,
    builder: (context) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: Icon(Icons.camera_alt),
            title: Text('Prendre une photo'),
            onTap: () async {
              Navigator.pop(context);
              final photo = await PhotoManager().pickImageFromCamera();
              if (photo != null) onPhotoSelected(photo);
            },
          ),
          ListTile(
            leading: Icon(Icons.photo_library),
            title: Text('Choisir de la galerie'),
            onTap: () async {
              Navigator.pop(context);
              final photo = await PhotoManager().pickImageFromGallery();
              if (photo != null) onPhotoSelected(photo);
            },
          ),
          ListTile(
            leading: Icon(Icons.cancel),
            title: Text('Annuler'),
            onTap: () => Navigator.pop(context),
          ),
        ],
      ),
    ),
  );
}
```

### 4. GPS Management

#### Get Current Location
Use `geolocator` package:

```dart
import 'package:geolocator/geolocator.dart';

class LocationService {
  Future<Position?> getCurrentLocation() async {
    bool serviceEnabled;
    LocationPermission permission;

    // Check if location services are enabled
    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      // Show error to user
      return null;
    }

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        return null;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      return null;
    }

    return await Geolocator.getCurrentPosition(
      desiredAccuracy: LocationAccuracy.high,
    );
  }
}
```

### 5. API Service Implementation

```dart
import 'dart:io';
import 'package:http/http.dart' as http;
import 'dart:convert';

class VisitReportService {
  final String baseUrl;
  final String token;

  VisitReportService({required this.baseUrl, required this.token});

  Future<VisitReport> createVisitReport({
    required int visitId,
    required double latitude,
    required double longitude,
    required bool managerPresent,
    required bool orderMade,
    required bool needsOrder,
    String? orderReference,
    double? orderEstimatedAmount,
    required bool stockShortageObserved,
    String? stockIssues,
    required bool competitorActivityObserved,
    String? competitorActivity,
    String? comments,
    List<File>? facadePhotos,
    List<File>? shelvesPhotos,
    List<File>? otherPhotos,
  }) async {
    var request = http.MultipartRequest(
      'POST',
      Uri.parse('$baseUrl/visits/$visitId/report'),
    );

    // Add headers
    request.headers['Authorization'] = 'Bearer $token';
    request.headers['Accept'] = 'application/json';

    // Add required fields
    request.fields['latitude'] = latitude.toString();
    request.fields['longitude'] = longitude.toString();

    // Add boolean fields
    request.fields['manager_present'] = managerPresent ? '1' : '0';
    request.fields['order_made'] = orderMade ? '1' : '0';
    request.fields['needs_order'] = needsOrder ? '1' : '0';
    request.fields['stock_shortage_observed'] = stockShortageObserved ? '1' : '0';
    request.fields['competitor_activity_observed'] = competitorActivityObserved ? '1' : '0';

    // Add optional text fields
    if (orderReference != null && orderReference.isNotEmpty) {
      request.fields['order_reference'] = orderReference;
    }
    if (orderEstimatedAmount != null) {
      request.fields['order_estimated_amount'] = orderEstimatedAmount.toString();
    }
    if (stockIssues != null && stockIssues.isNotEmpty) {
      request.fields['stock_issues'] = stockIssues;
    }
    if (competitorActivity != null && competitorActivity.isNotEmpty) {
      request.fields['competitor_activity'] = competitorActivity;
    }
    if (comments != null && comments.isNotEmpty) {
      request.fields['comments'] = comments;
    }

    // Add facade photos
    if (facadePhotos != null && facadePhotos.isNotEmpty) {
      for (var i = 0; i < facadePhotos.length; i++) {
        var photo = facadePhotos[i];
        var multipartFile = await http.MultipartFile.fromPath(
          'photo_facade[]',
          photo.path,
        );
        request.files.add(multipartFile);

        // Add GPS for this photo (same as report GPS)
        request.fields['photo_facade_gps[$i][latitude]'] = latitude.toString();
        request.fields['photo_facade_gps[$i][longitude]'] = longitude.toString();
      }
    }

    // Add shelves photos
    if (shelvesPhotos != null && shelvesPhotos.isNotEmpty) {
      for (var i = 0; i < shelvesPhotos.length; i++) {
        var photo = shelvesPhotos[i];
        var multipartFile = await http.MultipartFile.fromPath(
          'photo_shelves[]',
          photo.path,
        );
        request.files.add(multipartFile);

        request.fields['photo_shelves_gps[$i][latitude]'] = latitude.toString();
        request.fields['photo_shelves_gps[$i][longitude]'] = longitude.toString();
      }
    }

    // Add other photos
    if (otherPhotos != null && otherPhotos.isNotEmpty) {
      for (var i = 0; i < otherPhotos.length; i++) {
        var photo = otherPhotos[i];
        var multipartFile = await http.MultipartFile.fromPath(
          'photos_other[]',
          photo.path,
        );
        request.files.add(multipartFile);

        request.fields['photos_other_gps[$i][latitude]'] = latitude.toString();
        request.fields['photos_other_gps[$i][longitude]'] = longitude.toString();
      }
    }

    // Send request
    var streamedResponse = await request.send();
    var response = await http.Response.fromStream(streamedResponse);

    if (response.statusCode == 201) {
      final jsonResponse = json.decode(response.body);
      return VisitReport.fromJson(jsonResponse['data']);
    } else if (response.statusCode == 422) {
      final jsonResponse = json.decode(response.body);
      throw Exception(jsonResponse['message'] ?? 'Validation failed');
    } else {
      throw Exception('Failed to create visit report');
    }
  }

  Future<VisitReport?> getVisitReport(int visitId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/visits/$visitId/report'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final jsonResponse = json.decode(response.body);
      return VisitReport.fromJson(jsonResponse['data']);
    } else if (response.statusCode == 404) {
      return null;
    } else {
      throw Exception('Failed to load visit report');
    }
  }
}
```

### 6. State Management

#### Using Provider (Example)

```dart
import 'package:flutter/foundation.dart';
import 'dart:io';

class VisitReportProvider with ChangeNotifier {
  List<File> _facadePhotos = [];
  List<File> _shelvesPhotos = [];
  List<File> _otherPhotos = [];

  bool _managerPresent = false;
  bool _orderMade = false;
  bool _needsOrder = false;
  bool _stockShortageObserved = false;
  bool _competitorActivityObserved = false;

  String? _orderReference;
  double? _orderEstimatedAmount;
  String? _stockIssues;
  String? _competitorActivity;
  String? _comments;

  bool _isLoading = false;

  // Getters
  List<File> get facadePhotos => _facadePhotos;
  List<File> get shelvesPhotos => _shelvesPhotos;
  List<File> get otherPhotos => _otherPhotos;

  bool get managerPresent => _managerPresent;
  bool get orderMade => _orderMade;
  bool get needsOrder => _needsOrder;
  bool get stockShortageObserved => _stockShortageObserved;
  bool get competitorActivityObserved => _competitorActivityObserved;

  String? get orderReference => _orderReference;
  double? get orderEstimatedAmount => _orderEstimatedAmount;
  String? get stockIssues => _stockIssues;
  String? get competitorActivity => _competitorActivity;
  String? get comments => _comments;

  bool get isLoading => _isLoading;

  int get totalPhotosCount =>
      _facadePhotos.length + _shelvesPhotos.length + _otherPhotos.length;

  // Methods to add photos
  void addFacadePhoto(File photo) {
    _facadePhotos.add(photo);
    notifyListeners();
  }

  void removeFacadePhoto(int index) {
    _facadePhotos.removeAt(index);
    notifyListeners();
  }

  void addShelvesPhoto(File photo) {
    _shelvesPhotos.add(photo);
    notifyListeners();
  }

  void removeShelvesPhoto(int index) {
    _shelvesPhotos.removeAt(index);
    notifyListeners();
  }

  void addOtherPhoto(File photo) {
    _otherPhotos.add(photo);
    notifyListeners();
  }

  void removeOtherPhoto(int index) {
    _otherPhotos.removeAt(index);
    notifyListeners();
  }

  // Methods to update fields
  void setManagerPresent(bool value) {
    _managerPresent = value;
    notifyListeners();
  }

  void setOrderMade(bool value) {
    _orderMade = value;
    notifyListeners();
  }

  void setNeedsOrder(bool value) {
    _needsOrder = value;
    notifyListeners();
  }

  void setStockShortageObserved(bool value) {
    _stockShortageObserved = value;
    notifyListeners();
  }

  void setCompetitorActivityObserved(bool value) {
    _competitorActivityObserved = value;
    notifyListeners();
  }

  void setOrderReference(String? value) {
    _orderReference = value;
  }

  void setOrderEstimatedAmount(double? value) {
    _orderEstimatedAmount = value;
  }

  void setStockIssues(String? value) {
    _stockIssues = value;
  }

  void setCompetitorActivity(String? value) {
    _competitorActivity = value;
  }

  void setComments(String? value) {
    _comments = value;
  }

  // Submit report
  Future<void> submitReport(
    int visitId,
    double latitude,
    double longitude,
    VisitReportService service,
  ) async {
    _isLoading = true;
    notifyListeners();

    try {
      await service.createVisitReport(
        visitId: visitId,
        latitude: latitude,
        longitude: longitude,
        managerPresent: _managerPresent,
        orderMade: _orderMade,
        needsOrder: _needsOrder,
        orderReference: _orderReference,
        orderEstimatedAmount: _orderEstimatedAmount,
        stockShortageObserved: _stockShortageObserved,
        stockIssues: _stockIssues,
        competitorActivityObserved: _competitorActivityObserved,
        competitorActivity: _competitorActivity,
        comments: _comments,
        facadePhotos: _facadePhotos.isNotEmpty ? _facadePhotos : null,
        shelvesPhotos: _shelvesPhotos.isNotEmpty ? _shelvesPhotos : null,
        otherPhotos: _otherPhotos.isNotEmpty ? _otherPhotos : null,
      );

      // Reset form after success
      reset();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void reset() {
    _facadePhotos.clear();
    _shelvesPhotos.clear();
    _otherPhotos.clear();
    _managerPresent = false;
    _orderMade = false;
    _needsOrder = false;
    _stockShortageObserved = false;
    _competitorActivityObserved = false;
    _orderReference = null;
    _orderEstimatedAmount = null;
    _stockIssues = null;
    _competitorActivity = null;
    _comments = null;
    notifyListeners();
  }
}
```

### 7. Main Screen Widget Example

```dart
class CreateVisitReportScreen extends StatefulWidget {
  final int visitId;

  const CreateVisitReportScreen({Key? key, required this.visitId})
      : super(key: key);

  @override
  _CreateVisitReportScreenState createState() =>
      _CreateVisitReportScreenState();
}

class _CreateVisitReportScreenState extends State<CreateVisitReportScreen> {
  final _formKey = GlobalKey<FormState>();
  Position? _currentPosition;

  @override
  void initState() {
    super.initState();
    _getCurrentLocation();
  }

  Future<void> _getCurrentLocation() async {
    final position = await LocationService().getCurrentLocation();
    setState(() {
      _currentPosition = position;
    });
  }

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => VisitReportProvider(),
      child: Scaffold(
        appBar: AppBar(
          title: Text('Rapport de Visite'),
          actions: [
            Consumer<VisitReportProvider>(
              builder: (context, provider, _) {
                return IconButton(
                  icon: provider.isLoading
                      ? CircularProgressIndicator(color: Colors.white)
                      : Icon(Icons.check),
                  onPressed: provider.isLoading
                      ? null
                      : () => _submitReport(context, provider),
                );
              },
            ),
          ],
        ),
        body: _currentPosition == null
            ? Center(child: CircularProgressIndicator())
            : SingleChildScrollView(
                padding: EdgeInsets.all(16),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildPhotoSection(),
                      SizedBox(height: 24),
                      _buildObservationsSection(),
                      SizedBox(height: 24),
                      _buildOrderDetailsSection(),
                      SizedBox(height: 24),
                      _buildStockIssuesSection(),
                      SizedBox(height: 24),
                      _buildCompetitorActivitySection(),
                      SizedBox(height: 24),
                      _buildCommentsSection(),
                      SizedBox(height: 32),
                      _buildSubmitButton(),
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Widget _buildPhotoSection() {
    return Consumer<VisitReportProvider>(
      builder: (context, provider, _) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Facade photos
            _buildPhotoCategory(
              '📸 Photos de Façade',
              provider.facadePhotos,
              (photo) => provider.addFacadePhoto(photo),
              (index) => provider.removeFacadePhoto(index),
            ),
            SizedBox(height: 16),

            // Shelves photos
            _buildPhotoCategory(
              '📸 Photos Rayons',
              provider.shelvesPhotos,
              (photo) => provider.addShelvesPhoto(photo),
              (index) => provider.removeShelvesPhoto(index),
            ),
            SizedBox(height: 16),

            // Other photos
            _buildPhotoCategory(
              '📸 Photos Supplémentaires',
              provider.otherPhotos,
              (photo) => provider.addOtherPhoto(photo),
              (index) => provider.removeOtherPhoto(index),
            ),
          ],
        );
      },
    );
  }

  Widget _buildPhotoCategory(
    String title,
    List<File> photos,
    Function(File) onAdd,
    Function(int) onRemove,
  ) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
        ),
        SizedBox(height: 8),
        Container(
          height: 100,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: photos.length + 1,
            itemBuilder: (context, index) {
              if (index == photos.length) {
                // Add photo button
                return GestureDetector(
                  onTap: () => showPhotoSourceDialog(context, onAdd),
                  child: Container(
                    width: 100,
                    margin: EdgeInsets.only(right: 8),
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(Icons.add_a_photo, size: 40, color: Colors.grey),
                  ),
                );
              }

              // Photo thumbnail
              return Stack(
                children: [
                  Container(
                    width: 100,
                    margin: EdgeInsets.only(right: 8),
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.circular(8),
                      image: DecorationImage(
                        image: FileImage(photos[index]),
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                  Positioned(
                    top: 4,
                    right: 12,
                    child: GestureDetector(
                      onTap: () => onRemove(index),
                      child: Container(
                        padding: EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: Colors.red,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(Icons.close, color: Colors.white, size: 16),
                      ),
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildObservationsSection() {
    return Consumer<VisitReportProvider>(
      builder: (context, provider, _) {
        return Card(
          child: Padding(
            padding: EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '✅ Observations',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
                CheckboxListTile(
                  title: Text('Manager présent'),
                  value: provider.managerPresent,
                  onChanged: (value) => provider.setManagerPresent(value!),
                ),
                CheckboxListTile(
                  title: Text('Commande effectuée'),
                  value: provider.orderMade,
                  onChanged: (value) => provider.setOrderMade(value!),
                ),
                CheckboxListTile(
                  title: Text('Besoin de commander'),
                  value: provider.needsOrder,
                  onChanged: (value) => provider.setNeedsOrder(value!),
                ),
                CheckboxListTile(
                  title: Text('Rupture de stock observée'),
                  value: provider.stockShortageObserved,
                  onChanged: (value) =>
                      provider.setStockShortageObserved(value!),
                ),
                CheckboxListTile(
                  title: Text('Activité concurrente observée'),
                  value: provider.competitorActivityObserved,
                  onChanged: (value) =>
                      provider.setCompetitorActivityObserved(value!),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildSubmitButton() {
    return Consumer<VisitReportProvider>(
      builder: (context, provider, _) {
        return SizedBox(
          width: double.infinity,
          height: 50,
          child: ElevatedButton(
            onPressed: provider.isLoading
                ? null
                : () => _submitReport(context, provider),
            child: provider.isLoading
                ? CircularProgressIndicator(color: Colors.white)
                : Text('Enregistrer le Rapport', style: TextStyle(fontSize: 16)),
          ),
        );
      },
    );
  }

  Future<void> _submitReport(
    BuildContext context,
    VisitReportProvider provider,
  ) async {
    if (_currentPosition == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('GPS location not available')),
      );
      return;
    }

    try {
      await provider.submitReport(
        widget.visitId,
        _currentPosition!.latitude,
        _currentPosition!.longitude,
        VisitReportService(
          baseUrl: 'YOUR_API_URL',
          token: 'YOUR_TOKEN',
        ),
      );

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Rapport enregistré avec succès!')),
      );

      Navigator.pop(context, true);
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Erreur: $e')),
      );
    }
  }

  // Implement other section builders...
}
```

## Required Packages

Add these to `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0
  image_picker: ^1.0.4
  geolocator: ^10.1.0
  provider: ^6.1.1
  permission_handler: ^11.0.1
```

## Testing Checklist

- [ ] Take photo from camera for each category
- [ ] Select photo from gallery for each category
- [ ] Delete photo from each category
- [ ] Check/uncheck all checkboxes
- [ ] Fill order details when order_made is checked
- [ ] Fill stock issues when stock_shortage_observed is checked
- [ ] Fill competitor activity when competitor_activity_observed is checked
- [ ] Submit with GPS location
- [ ] Submit with only required fields (GPS)
- [ ] Submit with all fields filled
- [ ] Handle network errors gracefully
- [ ] Handle GPS permission denied
- [ ] Show loading state while uploading
- [ ] Navigate back after successful submission

## Important Notes

1. **GPS is Required**: Always get current location before allowing report submission
2. **Photo Compression**: Images are compressed to max 1920x1080 with 85% quality
3. **Photo Limits**: Recommend max 5-7 photos per category for better UX
4. **Offline Support**: Consider implementing local storage and sync when online
5. **Progress Indicator**: Show upload progress for better UX
6. **Validation**: Validate that at least latitude and longitude are present before submission

## Success Criteria

The feature is complete when:
- ✅ User can add multiple photos in each category
- ✅ User can fill all observation checkboxes
- ✅ User can add optional text details
- ✅ GPS coordinates are captured and sent
- ✅ Report is submitted successfully to API
- ✅ Success/error messages are shown
- ✅ User is navigated back after successful submission
- ✅ Loading states are properly displayed
